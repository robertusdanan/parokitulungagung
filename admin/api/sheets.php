<?php
/**
 * admin/api/sheets.php — API CRUD via Supabase
 * Pengganti SheetsClient-based API. Struktur request/response sama persis.
 */
ob_start(); // tangkap notice/warning agar tidak merusak JSON response
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();

header('Content-Type: application/json; charset=utf-8');

$body   = jsonBody();
$action = $body['action'] ?? '';
$page   = $body['page']   ?? '';

if (!$page || !in_array($page, ALL_PAGES)) {
    apiJson(['error' => 'Halaman tidak valid: ' . $page], 400);
}

// Master data: list boleh semua admin, create/update/delete hanya superadmin
$masterPages = ['master_lingkungan', 'master_bidang', 'master_koordinator'];
if (in_array($page, $masterPages)) {
    $currentUser = apiRequireLogin();
    $isSA = $currentUser['role'] === ROLE_SUPERADMIN;
    if (!$isSA) {
        $permsMap = getPermissionsMap($currentUser);
        $hasMaster = array_key_exists('master', $permsMap);
        if (!$hasMaster) {
            apiJson(['error' => 'Hanya superadmin atau user dengan akses Master yang dapat mengubah data master'], 403);
        }
        // Cek aksi spesifik
        $masterActions = $permsMap['master'] ?? ['view'];
        $actionMap2 = ['list'=>'view','create'=>'create','update'=>'edit','delete'=>'delete'];
        $req2 = $actionMap2[$action] ?? 'view';
        if ($req2 !== 'view' && !in_array($req2, $masterActions)) {
            apiJson(['error' => 'Akses ditolak untuk aksi ini pada data master'], 403);
        }
    }
} else {
    $actionMap = [
        'list'   => 'view',
        'create' => 'create',
        'update' => 'edit',
        'delete' => 'delete',
    ];
    $requiredAction = $actionMap[$action] ?? 'view';
    $currentUser = apiRequirePageAccess($page, $requiredAction);
}

/**
 * Peta halaman → tabel Supabase + kolom yang ditampilkan (urutan = urutan asli spreadsheet)
 *
 * Catatan:
 * - 'table'  : nama tabel di Supabase (sesuai hasil migrasi)
 * - 'cols'   : kolom yang dikembalikan ke frontend (tidak termasuk 'id' internal Supabase
 *               kecuali memang diperlukan; 'id' selalu ditambahkan otomatis sebagai _id)
 * - 'pk'     : primary key tabel di Supabase (default 'id')
 * - 'order'  : default ordering
 */
$tableConfig = [
    'galeri' => [
        'table' => TABLE_GALERI,
        'cols'  => ['Tanggal','Bulan','Judul','Gambar','Foto','Link','Keterangan'],
        'pk'    => 'id',
        'order' => 'id.asc',
    ],
    'petugas' => [
        'table' => TABLE_PETUGAS,
        'cols'  => ['No','HariTanggal','Pekan','Koor','Organis','Pemazmur','Lektor','Pemandu','Dekorasi','Asisten','Saran'],
        'pk'    => 'id',
        'order' => 'id.asc',
    ],
    'wilayah' => [
        'table' => TABLE_WILAYAH,
        'cols'  => ['Wilayah','Lingkungan','Ketua','Foto','Koordinator','KoordinatorNama','KoordinatorFoto','Periode'],
        'pk'    => 'id',
        'order' => 'Wilayah.asc',
    ],
    'asisten_imam' => [
        'table' => TABLE_AI,
        'cols'  => ['Nama','No.HP','Asal Lingk / Stasi','Alamat','Periode','Foto'],
        'pk'    => 'id',
        'order' => 'Nama.asc',
    ],
    'dpp_bgkp' => [
        'table' => TABLE_DPP,
        'cols'  => ['Bidang','Posisi','Nama','Tipe','Periode','Foto'],
        'pk'    => 'id',
        'order' => 'id.asc',
    ],
    'agenda' => [
        'table' => TABLE_AGENDA,
        'cols'  => ['tanggal','bulan','judul','keterangan','icon','hari_libur'],
        'pk'    => 'id',
        'order' => 'tanggal.asc',
    ],
    'umkm' => [
        'table' => TABLE_UMKM,
        'cols'  => ['judul','nama_usaha','kontak','deskripsi','gambar','urutan','aktif','status','created_by'],
        'pk'    => 'id',
        'order' => 'urutan.asc,id.desc',
    ],
    'dokumen_paroki' => [
    'table' => TABLE_DOKUMEN_PAROKI,
    'cols'  => ['judul', 'deskripsi', 'nama_file', 'ukuran', 'kategori', 'urutan', 'aktif'],
    'pk'    => 'id',
    'order' => 'urutan.asc,id.desc',
	],
    'master_koordinator' => [
        'table' => TABLE_MASTER_KOOR,
        'cols'  => ['Nama','Periode','Foto'],
        'pk'    => 'id',
        'order' => 'Periode.desc,Nama.asc',
    ],
    'master_lingkungan' => [
        'table' => TABLE_MASTER_LINGK,
        'cols'  => ['Wilayah','Lingkungan'],
        'pk'    => 'id',
        'order' => 'Wilayah.asc',
    ],
    'master_bidang' => [
        'table' => TABLE_MASTER_BIDANG,
        'cols'  => ['Tipe','Bidang','Periode'],
        'pk'    => 'id',
        'order' => 'Periode.desc,Tipe.asc',
    ],
    'jadwal_petugas_gambar' => [
    'table' => 'jadwal_petugas_gambar', // nama tabel di Supabase
    'cols'  => ['judul', 'nama_file', 'urutan', 'aktif'],
    'pk'    => 'id',
    'order' => 'urutan.asc,id.desc',
    ],

];

if (!isset($tableConfig[$page])) {
    apiJson(['error' => 'Konfigurasi halaman tidak ditemukan'], 500);
}

$cfg   = $tableConfig[$page];
$table = $cfg['table'];
$cols  = $cfg['cols'];
$pk    = $cfg['pk'];
$order = $cfg['order'];

try {
    $db     = getDB();
    $logger = getLogger();

    switch ($action) {

        // ── LIST ───────────────────────────────────────────────────────
        case 'list':
            $rows = $db->read($table, [], $order);
            $data = [];
            foreach ($rows as $row) {
                $item = [];
                foreach ($cols as $col) {
                    $item[$col] = $row[$col] ?? '';
                }
                // Kirim _id (primary key Supabase) agar frontend bisa pakai untuk update/delete
                $item['_id']  = $row[$pk] ?? null;
                // Kompatibilitas lama: _row tidak ada lagi, tapi _id menggantikan fungsinya
                $item['_row'] = $row[$pk] ?? null;
                $data[] = $item;
            }
            apiJson(['success' => true, 'data' => $data]);
            break;

        // ── CREATE ────────────────────────────────────────────────────
        case 'create':
            $rowData = $body['data'] ?? [];
            $insert  = [];
            foreach ($cols as $col) {
                $insert[$col] = $rowData[$col] ?? '';
            }
            $result = $db->insert($table, $insert);
            $logger->log($currentUser, 'CREATE', $page,
                'Baris baru: ' . json_encode(array_slice($insert, 0, 2), JSON_UNESCAPED_UNICODE));
            apiJson(['success' => true, 'id' => $result[$pk] ?? null]);
            break;

        // ── UPDATE ────────────────────────────────────────────────────
        case 'update':
            // Frontend mengirim _id (UUID/integer Supabase)
            $recordId = $body['id'] ?? $body['row'] ?? null;
            if (!$recordId) {
                apiJson(['error' => 'ID record tidak valid'], 400);
            }
            $rowData = $body['data'] ?? [];
            $update  = [];
            foreach ($cols as $col) {
                if (array_key_exists($col, $rowData)) {
                    $update[$col] = $rowData[$col];
                }
            }
            $db->update($table, $pk, $recordId, $update);
            $logger->log($currentUser, 'UPDATE', $page, 'Update ID: ' . $recordId);
            apiJson(['success' => true]);
            break;

        // ── DELETE ────────────────────────────────────────────────────
        case 'delete':
            $recordId = $body['id'] ?? $body['row'] ?? null;
            if (!$recordId) {
                apiJson(['error' => 'ID record tidak valid'], 400);
            }
            $db->delete($table, $pk, $recordId);
            $logger->log($currentUser, 'DELETE', $page, 'Hapus ID: ' . $recordId);
            apiJson(['success' => true]);
            break;

        default:
            apiJson(['error' => 'Action tidak dikenal: ' . $action], 400);
    }

} catch (Throwable $e) {
    error_log('[sheets.php] ' . $e->getMessage());
    apiJson(['error' => $e->getMessage()], 500);
}