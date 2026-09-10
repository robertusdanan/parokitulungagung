<?php
/**
 * admin/pages/cache.php — Sistem → Cache
 * Kelola semua cache situs (website & admin) dari satu tempat.
 * Akses: superadmin (sama seperti Manajemen User / Log Aktivitas / SEO Generator).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
adminBoot();
$user = requireSuperadmin();

$groupIcons = [
    'supabase'  => '<path d="M4 7v10c0 1.66 3.58 3 8 3s8-1.34 8-3V7"/><ellipse cx="12" cy="7" rx="8" ry="3"/>',
    'runtime'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    'galeri'    => '<path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>',
    'r2img'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
    'analytics' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-4"/>',
    'imgseo'    => '<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35M11 8v6M8 11h6"/>',
    'sitemap'   => '<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v6m0 0L5 17m7-4l7 4"/>',
    'media'     => '<rect x="3" y="3" width="18" height="14" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><polyline points="20 15 14 9 5 17"/>',
    'chatbot'   => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>',
];

adminHeader('Cache', 'cache', $user);
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Cache</h1>
    <p>Kelola semua cache situs — pilih folder cache yang ingin dibersihkan</p>
  </div>
  <div class="page-header-right">
    <button class="btn btn-danger btn-sm" id="btnFlushAll" onclick="flushCacheGroup('*', 'Semua Cache')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
        <polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/>
        <path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/>
      </svg>
      Bersihkan Semua Cache
    </button>
  </div>
</div>

<div id="cacheGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:20px">
  <div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">Memuat status cache…</div>
</div>

<div class="card" style="font-size:12px;color:var(--text-muted);line-height:1.6">
  <strong style="color:var(--text-secondary)">Catatan:</strong>
  Cache dibuat otomatis kembali saat halaman terkait diakses — menghapusnya tidak menghapus data asli
  (Supabase / R2 / gambar), hanya memaksa pengambilan data terbaru pada kunjungan berikutnya.
  Cache Chatbot yang dihapus di sini hanya mencakup konteks jawaban; data rate-limit &amp; rotasi API key
  tidak ikut terhapus.
</div>

<script>
const GROUP_ICONS = <?= json_encode($groupIcons) ?>;

function fmtDate(ts) {
  if (!ts) return '—';
  const d = new Date(ts * 1000);
  return d.toLocaleDateString('id-ID', { day:'2-digit', month:'2-digit' }) + ' ' +
         d.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
}

function renderCacheGrid(groups) {
  const grid = document.getElementById('cacheGrid');
  const keys = Object.keys(groups);
  if (!keys.length) {
    grid.innerHTML = '<div class="card" style="padding:30px;text-align:center;color:var(--text-muted)">Tidak ada grup cache terdaftar.</div>';
    return;
  }
  grid.innerHTML = keys.map(key => {
    const g = groups[key];
    const icon = GROUP_ICONS[key] || '<circle cx="12" cy="12" r="9"/>';
    const hasData = g.count > 0;
    return `
    <div class="card" style="padding:18px;display:flex;flex-direction:column;gap:10px" data-group="${key}">
      <div style="display:flex;align-items:flex-start;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.05);display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.8" width="18" height="18">${icon}</svg>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13.5px;font-weight:600;color:var(--text-primary)">${g.label}</div>
          <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;line-height:1.5">${g.description}</div>
        </div>
      </div>
      <div style="display:flex;gap:14px;font-size:12px;color:var(--text-secondary);padding:8px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
        <div><span style="color:${hasData ? 'var(--success)' : 'var(--text-muted)'};font-weight:600">${g.count}</span> file</div>
        <div>${g.size_kb} KB</div>
        <div>Terlama: ${fmtDate(g.oldest)}</div>
      </div>
      <button class="btn btn-secondary btn-sm" style="align-self:flex-start" ${hasData ? '' : 'disabled'}
              onclick="flushCacheGroup('${key}', '${g.label.replace(/'/g, "\\'")}')">
        Bersihkan (${g.count})
      </button>
    </div>`;
  }).join('');
}

async function loadCacheStatus() {
  try {
    const res = await fetch('/admin/api/cache.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'status' })
    });
    const data = await res.json();
    if (data.success) {
      renderCacheGrid(data.groups);
    } else {
      document.getElementById('cacheGrid').innerHTML =
        `<div class="card" style="padding:20px;color:var(--danger)">Gagal memuat status: ${data.error || ''}</div>`;
    }
  } catch (e) {
    document.getElementById('cacheGrid').innerHTML =
      '<div class="card" style="padding:20px;color:var(--danger)">Gagal menghubungi server.</div>';
  }
}

function flushCacheGroup(group, label) {
  const msg = group === '*'
    ? 'Hapus SEMUA cache situs? Halaman publik & admin akan mengambil data terbaru pada kunjungan berikutnya.'
    : `Hapus cache "${label}"? Data akan diambil ulang pada kunjungan berikutnya.`;

  confirmDialog(group === '*' ? 'Bersihkan Semua Cache' : `Bersihkan ${label}`, msg, async () => {
    try {
      const res = await fetch('/admin/api/cache.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'flush', group })
      });
      const data = await res.json();
      if (data.success) {
        toast('Cache Dihapus', data.message, 'success');
        loadCacheStatus();
      } else {
        toast('Error', data.error || 'Gagal menghapus cache', 'error');
      }
    } catch (e) {
      toast('Error', 'Gagal menghubungi server', 'error');
    }
  });
}

document.addEventListener('DOMContentLoaded', loadCacheStatus);
</script>

<?php adminFooter(); ?>
