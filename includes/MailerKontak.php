<?php
/**
 * includes/MailerKontak.php
 *
 * Kirim email dari halaman kontak publik via cPanel SMTP (SSL port 465)
 * menggunakan PHP raw socket — TANPA library eksternal (PHPMailer tidak diperlukan).
 *
 * Berbeda dengan admin/includes/Mailer.php yang memakai Gmail SMTP + STARTTLS (port 587),
 * kelas ini memakai cPanel hosting SMTP + SSL langsung (port 465) sesuai konfigurasi
 * akun support@parokitulungagung.org.
 *
 * Method publik:
 *   sendToAdmin($subject, $htmlBody, $userEmail, $userNama)  — notifikasi ke sekretariat
 *   sendToUser($email, $nama, $subject, $htmlBody)           — konfirmasi ke pengirim
 */

class MailerKontak
{
    // ── Konfigurasi SMTP cPanel ──────────────────────────────────────────
    private string $host     = 'mail.parokitulungagung.org';
    private int    $port     = 465;          // SSL langsung (bukan STARTTLS)
    private string $from     = 'support@parokitulungagung.org';
    private string $fromName = 'Paroki Tulungagung';
    private string $username = 'support@parokitulungagung.org';
    private string $password = '753214Arjuna';

    // Email tujuan admin/sekretariat
    private string $adminEmail = 'sanmardtba@gmail.com';
    private string $adminName  = 'Sekretariat Paroki';

    private array $log = [];

    // ────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ────────────────────────────────────────────────────────────────────

    /**
     * Kirim notifikasi ke admin/sekretariat.
     * Reply-To diset ke email pengirim agar admin tinggal klik Reply.
     *
     * @throws RuntimeException
     */
    public function sendToAdmin(
        string $subject,
        string $htmlBody,
        string $userEmail = '',
        string $userNama  = ''
    ): void {
        $extraHeaders = [];
        if ($userEmail) {
            $replyName        = $userNama ?: 'Pengirim';
            $extraHeaders[]   = "Reply-To: =?UTF-8?B?" . base64_encode($replyName) . "?= <{$userEmail}>";
        }

        $this->deliver(
            $this->adminEmail,
            $this->adminName,
            $subject,
            $htmlBody,
            $extraHeaders
        );
    }

    /**
     * Kirim email konfirmasi ke pengirim.
     *
     * @throws RuntimeException
     */
    public function sendToUser(
        string $toEmail,
        string $toNama,
        string $subject,
        string $htmlBody
    ): void {
        $this->deliver($toEmail, $toNama, $subject, $htmlBody);
    }

    /** Ambil log SMTP untuk debug (opsional) */
    public function getLog(): array { return $this->log; }

    // ────────────────────────────────────────────────────────────────────
    // INTERNAL
    // ────────────────────────────────────────────────────────────────────

    /**
     * Core: buka koneksi SSL, handshake SMTP, kirim pesan, tutup.
     *
     * @param string[] $extraHeaders Baris header tambahan (misal Reply-To)
     * @throws RuntimeException
     */
    private function deliver(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array  $extraHeaders = []
    ): void {
        $sock = $this->connect();

        try {
            // Banner sudah dibaca di connect(); langsung EHLO
            $this->smtp($sock, "EHLO localhost",                 ['250']);
            $this->smtp($sock, "AUTH LOGIN",                     ['334']);
            $this->smtp($sock, base64_encode($this->username),   ['334']);
            $this->smtp($sock, base64_encode($this->password),   ['235']);
            $this->smtp($sock, "MAIL FROM:<{$this->from}>",      ['250']);
            $this->smtp($sock, "RCPT TO:<{$toEmail}>",           ['250', '251']);
            $this->smtp($sock, "DATA",                           ['354']);

            $message = $this->buildMessage($toEmail, $toName, $subject, $htmlBody, $extraHeaders);
            // Titik tunggal di baris sendiri menandai akhir DATA
            $this->smtp($sock, $message . "\r\n.",                ['250']);
            $this->smtp($sock, "QUIT",                           ['221']);

        } finally {
            fclose($sock);
        }
    }

    /**
     * Buka koneksi SSL langsung ke port 465 (smtps).
     * Berbeda dengan Mailer.php (admin) yang pakai tcp:// + STARTTLS.
     */
    private function connect()
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $errno  = 0;
        $errstr = '';
        $sock   = stream_socket_client(
            "ssl://{$this->host}:{$this->port}",   // ← ssl:// bukan tcp://
            $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx
        );

        if (!$sock) {
            throw new RuntimeException(
                "MailerKontak: Gagal connect ke {$this->host}:{$this->port} — {$errstr} ({$errno})"
            );
        }

        stream_set_timeout($sock, 15);

        // Baca banner 220 (bisa multi-line: 220- ... lalu 220 spasi)
        $bannerCode = '';
        while (true) {
            $line = fgets($sock, 512);
            if ($line === false) break;
            $this->log[] = "S: " . trim($line);
            $bannerCode = substr(trim($line), 0, 3);
            // Baris terakhir ditandai karakter ke-4 adalah spasi (bukan '-')
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }

        if ($bannerCode !== '220') {
            throw new RuntimeException("MailerKontak: Banner SMTP tidak valid: " . $bannerCode);
        }

        return $sock;
    }

    /**
     * Kirim satu perintah SMTP dan validasi kode respons.
     */
    private function smtp($sock, string $cmd, array $expectCodes): string
    {
        fwrite($sock, $cmd . "\r\n");

        // Log — sembunyikan password
        if ($cmd === base64_encode($this->password)) {
            $this->log[] = "C: [PASSWORD]";
        } else {
            $this->log[] = "C: " . (str_contains($cmd, "\r\n") ? '[DATA BODY]' : $cmd);
        }

        // Baca respons (bisa multi-line, diakhiri baris "NNN " dengan spasi)
        $response = '';
        while (true) {
            $line = fgets($sock, 512);
            if ($line === false) break;
            $this->log[] = "S: " . trim($line);
            $response   .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }

        $code = substr(trim($response), 0, 3);
        if (!in_array($code, $expectCodes)) {
            throw new RuntimeException(
                "MailerKontak SMTP error — perintah: [{$cmd}] respons: " . trim($response)
            );
        }

        return trim($response);
    }

    /**
     * Rakit pesan MIME multipart/alternative (plain-text + HTML).
     *
     * @param string[] $extraHeaders
     */
    private function buildMessage(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array  $extraHeaders = []
    ): string {
        $boundary = '----=_KontakPart_' . md5(uniqid('', true));

        // Buat versi plain-text dari HTML body
        $plain = strip_tags(str_replace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>'],
            "\n",
            $htmlBody
        ));
        $plain = html_entity_decode(preg_replace('/\s+/', ' ', $plain), ENT_QUOTES, 'UTF-8');

        $baseHeaders = [
            "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->from}>",
            "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: SMDTBA-MailerKontak/2.0",
            "Date: " . date('r'),
        ];

        // Sisipkan extra headers (misal Reply-To) setelah From
        $allHeaders = array_merge($baseHeaders, $extraHeaders, ['']);

        $headers = implode("\r\n", $allHeaders);

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
}