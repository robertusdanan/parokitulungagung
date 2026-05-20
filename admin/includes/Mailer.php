<?php
/**
 * admin/includes/Mailer.php
 * Kirim email via Gmail SMTP menggunakan PHP socket (tanpa PHPMailer/library eksternal)
 * Compatible dengan InfinityFree (tidak pakai mail() bawaan PHP yang diblokir)
 *
 * Cara pakai:
 *   $mailer = new Mailer();
 *   $mailer->send('tujuan@email.com', 'Nama Tujuan', 'Subjek', '<p>Body HTML</p>');
 */

class Mailer
{
    private string $host     = 'smtp.gmail.com';
    private int    $port     = 587;
    private string $from;
    private string $fromName;
    private string $username;
    private string $password; // Gmail App Password (16 karakter)
    private array  $log      = [];

    public function __construct()
    {
        $this->from     = defined('MAIL_FROM')      ? MAIL_FROM      : '';
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Admin SMDTBA';
        $this->username = defined('MAIL_USERNAME')  ? MAIL_USERNAME  : '';
        $this->password = defined('MAIL_PASSWORD')  ? MAIL_PASSWORD  : '';
    }

    /**
     * Kirim email.
     * @throws RuntimeException jika pengiriman gagal
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        if (!$this->from || !$this->password) {
            throw new RuntimeException('Konfigurasi email (MAIL_FROM / MAIL_PASSWORD) belum diisi di config.php.');
        }

        $sock = $this->connect();
        try {
            $this->smtp($sock, "EHLO localhost",         ['250']);
            $this->smtp($sock, "STARTTLS",               ['220']);

            // Upgrade ke TLS
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Gagal upgrade ke TLS.');
            }

            $this->smtp($sock, "EHLO localhost",         ['250']);
            $this->smtp($sock, "AUTH LOGIN",             ['334']);
            $this->smtp($sock, base64_encode($this->username), ['334']);
            $this->smtp($sock, base64_encode($this->password), ['235']);
            $this->smtp($sock, "MAIL FROM:<{$this->from}>", ['250']);
            $this->smtp($sock, "RCPT TO:<{$toEmail}>",   ['250', '251']);
            $this->smtp($sock, "DATA",                   ['354']);

            $message = $this->buildMessage($toEmail, $toName, $subject, $htmlBody);
            $this->smtp($sock, $message . "\r\n.",        ['250']);
            $this->smtp($sock, "QUIT",                   ['221']);
        } finally {
            fclose($sock);
        }
    }

    // ── Internals ─────────────────────────────────────────────────────

    private function connect()
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $errno = 0; $errstr = '';
        $sock  = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx
        );

        if (!$sock) {
            throw new RuntimeException("Gagal connect ke SMTP {$this->host}:{$this->port} — {$errstr} ({$errno})");
        }

        stream_set_timeout($sock, 15);
        $banner = fgets($sock, 512);
        $this->log[] = "S: " . trim($banner);

        if (!str_starts_with(trim($banner), '220')) {
            throw new RuntimeException("SMTP banner tidak valid: " . trim($banner));
        }

        return $sock;
    }

    private function smtp($sock, string $cmd, array $expectCodes): string
    {
        // Tulis command (kecuali DATA body yang sudah berisi \r\n.)
        if (!str_contains($cmd, "\r\n.")) {
            fwrite($sock, $cmd . "\r\n");
            $this->log[] = "C: " . (str_starts_with($cmd, base64_encode($this->password)) ? 'C: [PASSWORD]' : "C: {$cmd}");
        } else {
            fwrite($sock, $cmd . "\r\n");
            $this->log[] = "C: [DATA BODY]";
        }

        // Baca respons (bisa multi-line)
        $response = '';
        while (true) {
            $line = fgets($sock, 512);
            if ($line === false) break;
            $this->log[] = "S: " . trim($line);
            $response   .= $line;
            // Baris terakhir: "250 " (space setelah kode, bukan dash)
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }

        $code = substr(trim($response), 0, 3);
        if (!in_array($code, $expectCodes)) {
            throw new RuntimeException("SMTP error. Command: [{$cmd}] Response: " . trim($response));
        }

        return trim($response);
    }

    private function buildMessage(string $toEmail, string $toName, string $subject, string $htmlBody): string
    {
        $boundary = '----=_Part_' . md5(uniqid());
        $plain    = strip_tags(str_replace(['<br>', '<br/>', '<br />','</p>','</div>'], "\n", $htmlBody));
        $plain    = html_entity_decode(preg_replace('/\s+/', ' ', $plain), ENT_QUOTES, 'UTF-8');

        $headers = implode("\r\n", [
            "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->from}>",
            "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: SMDTBA-Mailer/1.0",
            "Date: " . date('r'),
            "",
        ]);

        $body = "--{$boundary}\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($plain)) . "\r\n"
              . "--{$boundary}\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($htmlBody)) . "\r\n"
              . "--{$boundary}--";

        return $headers . $body;
    }

    /** Ambil log SMTP untuk debug */
    public function getLog(): array { return $this->log; }
}