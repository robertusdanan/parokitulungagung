<?php
/**
 * includes/GitHubDispatcher.php
 * ─────────────────────────────────────────────────────────────────────────
 * Memicu GitHub Actions lewat REST API "repository_dispatch" — dipakai
 * untuk melempar tugas KOMPRESI VIDEO ke runner GitHub Actions (yang sudah
 * punya ffmpeg bawaan di ubuntu-latest) saat hosting/shared server TIDAK
 * menyediakan ffmpeg/proc_open.
 *
 * PENTING — pola BATCH + SELF-DISCOVERY (bukan 1 dispatch per video, dan
 * BUKAN pula mengirim daftar file dalam payload): kalau 1 folder/album
 * berisi banyak video sekaligus, video-video itu ditaruh dulu di staging R2
 * dengan key DETERMINISTIK ({R2_PENDING_VIDEO_PREFIX}{album}/{relpath}) oleh
 * r2_folder_upload.php. Setelah SEMUA file di folder selesai diupload,
 * r2_video_batch_dispatch.php memanggil dispatch() SATU KALI — payload-nya
 * CUMA nama album + prefix (KECIL, tidak tergantung jumlah video) — dan
 * GitHub Actions sendiri yang men-scan isi staging album itu lalu memproses
 * SEMUA video yang ditemukan berurutan lewat loop ffmpeg dalam 1 run yang
 * sama (lihat compress-video.yml di repo workflow).
 *
 * Cara pakai singkat:
 *   $gh = new GitHubDispatcher(SECRET_GITHUB_TOKEN, SECRET_GITHUB_REPO);
 *   $gh->dispatch('compress-video-batch', [
 *       'album'          => 'Misa Natal 2024',
 *       'staging_prefix' => '_pending_video/Misa Natal 2024/',
 *       'target_prefix'  => 'galerifoto/Misa Natal 2024/',
 *   ]);
 *
 * Butuh 2 konstanta di private/secrets.php:
 *   define('SECRET_GITHUB_TOKEN', 'ghp_xxx atau github_pat_xxx');
 *     -> Personal Access Token (fine-grained), scope MINIMAL:
 *        "Actions: Read and write" pada repo tujuan saja (tidak perlu akses
 *        repo lain / kode sumber apapun).
 *   define('SECRET_GITHUB_REPO', 'namauser/nama-repo');
 *     -> repo KHUSUS berisi workflow .github/workflows/compress-video.yml
 *        (boleh privat, terpisah dari repo source code website).
 *
 * repository_dispatch SELALU membalas HTTP 204 (No Content) kalau sukses —
 * TIDAK ada run_id yang dikembalikan langsung, karena GitHub menjadwalkan
 * run-nya secara async. Untuk melihat progres, admin diarahkan ke tab
 * Actions repo tsb (lihat link yang dikembalikan oleh actionsUrl()).
 * ─────────────────────────────────────────────────────────────────────────
 */

final class GitHubDispatchException extends \RuntimeException {}

final class GitHubDispatcher
{
    private string $token;
    private string $repo; // format: "owner/repo"

    public function __construct(string $token, string $repo)
    {
        $this->token = $token;
        $this->repo  = trim($repo, '/');
    }

    /**
     * Kirim event repository_dispatch ke GitHub Actions.
     *
     * @param string $eventType nama event — harus cocok dengan
     *                          `on.repository_dispatch.types` di workflow YAML
     * @param array  $payload   data custom (JANGAN taruh secret di sini —
     *                          client_payload tampil di log run GitHub Actions).
     *                          Muncul di workflow sebagai github.event.client_payload.*
     * @throws GitHubDispatchException kalau request gagal / bukan HTTP 204
     */
    public function dispatch(string $eventType, array $payload = []): void
    {
        if (!function_exists('curl_init')) {
            throw new GitHubDispatchException('Ekstensi cURL tidak tersedia di server ini.');
        }

        $url  = "https://api.github.com/repos/{$this->repo}/dispatches";
        $body = json_encode(
            ['event_type' => $eventType, 'client_payload' => $payload],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $this->token,
                'X-GitHub-Api-Version: 2022-11-28',
                'User-Agent: media-manager-video-dispatcher',
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new GitHubDispatchException('cURL error: ' . $curlErr);
        }
        if ($httpCode !== 204) {
            throw new GitHubDispatchException("GitHub API balas HTTP {$httpCode}: " . substr((string)$response, 0, 300));
        }
    }

    /** Link tab Actions repo (khusus workflow compress-video), untuk ditampilkan ke admin. */
    public function actionsUrl(string $workflowFile = 'compress-video.yml'): string
    {
        return "https://github.com/{$this->repo}/actions/workflows/{$workflowFile}";
    }
}
