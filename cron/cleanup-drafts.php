<?php
/**
 * cron/cleanup-drafts.php
 * Script otomatisasi cPanel / CLI untuk membersihkan artikel berstatus
 * draft atau revisi yang tidak diupdate lebih dari 30 hari (1 bulan).
 *
 * CARA JALANKAN DI CPANEL CRON JOBS:
 * CLI (Disarankan):
 * /usr/local/bin/php /home/username/public_html/cron/cleanup-drafts.php >/dev/null 2>&1
 *
 * Atau via URL Browser / Wget / Curl (Wajib sertakan token secret jika lewat web):
 * curl -s "https://www.parokitulungagung.org/cron/cleanup-drafts.php?token=SECRET_KEY"
 */

// ── Security Check & Bootstrap ─────────────────────────────────────────
$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/SupabaseArticleManager.php';

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    $secretToken = defined('SECRET_CRON_KEY') ? SECRET_CRON_KEY : (defined('SECRET_SUPABASE_ANON_KEY') ? substr(SECRET_SUPABASE_ANON_KEY, 0, 16) : 'paroki_cron_secret');
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    if (!hash_equals($secretToken, (string)$token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak: Token cron tidak valid'], JSON_PRETTY_PRINT);
        exit;
    }
}

$thresholdDays = 30;
$cutoffTime = time() - ($thresholdDays * 86400);
$cutoffDate = date('Y-m-d H:i:s', $cutoffTime);

$am = new SupabaseArticleManager();
$deletedArticles = [];
$errors = [];

foreach (SupabaseArticleManager::MENUS as $menu) {
    $all = $am->getAll($menu);
    if (!is_array($all)) continue;

    foreach ($all as $art) {
        $status = $art['status'] ?? 'draft';
        // Hanya hapus draft atau revisi (JANGAN PERNAH HAPUS PUBLISHED)
        if ($status !== 'draft' && $status !== 'revisi') {
            continue;
        }

        // Tentukan timestamp aktivitas terakhir
        $tsStr = $art['updated_at'] ?? $art['created_at'] ?? $art['tanggal'] ?? '';
        $artTime = !empty($tsStr) ? strtotime($tsStr) : 0;

        if ($artTime > 0 && $artTime < $cutoffTime) {
            $artId    = $art['id'] ?? '';
            $artJudul = $art['judul'] ?? 'Tanpa Judul';
            $penulis  = $art['penulis'] ?? '—';

            if ($artId) {
                try {
                    $ok = $am->delete($menu, $artId);
                    if ($ok) {
                        $deletedArticles[] = [
                            'id'         => $artId,
                            'menu'       => $menu,
                            'judul'      => $artJudul,
                            'penulis'    => $penulis,
                            'status'     => $status,
                            'last_aktif' => $tsStr,
                        ];
                    } else {
                        $errors[] = "Gagal menghapus ID {$artId} di menu {$menu}";
                    }
                } catch (Throwable $e) {
                    $errors[] = "Error ID {$artId}: " . $e->getMessage();
                }
            }
        }
    }
}

$summary = [
    'success'          => true,
    'waktu_eksekusi'   => date('Y-m-d H:i:s'),
    'batas_kedaluwarsa'=> $cutoffDate,
    'total_dihapus'    => count($deletedArticles),
    'artikel_dihapus'  => $deletedArticles,
    'errors'           => $errors,
];

if ($isCli) {
    echo "========================================================
";
    echo " AUTO CLEANUP DRAFT / REVISI KEDALUWARSA (> 30 HARI)
";
    echo " Waktu Eksekusi: {$summary['waktu_eksekusi']}
";
    echo " Batas Tanggal : {$summary['batas_kedaluwarsa']}
";
    echo " Total Dihapus : {$summary['total_dihapus']} artikel
";
    echo "========================================================
";
    foreach ($deletedArticles as $d) {
        echo "- [{$d['menu']}] [{$d['status']}] {$d['judul']} (Penulis: {$d['penulis']}, Terakhir: {$d['last_aktif']})
";
    }
    if (!empty($errors)) {
        echo "
ERRORS:
" . implode("
", $errors) . "
";
    }
} else {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
