/**
 * admin/js/admin.js
 * Interaktivitas Admin Panel SMDTBA
 */

// ── Toast Notifications ──────────────────────────────────────────────
function toast(title, msg = '', type = 'info', duration = 3500) {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `
    <div>
      <div class="toast-title">${escHtml(title)}</div>
      ${msg ? `<div class="toast-msg">${escHtml(msg)}</div>` : ''}
    </div>
    <button class="toast-close" onclick="this.closest('.toast').remove()">&times;</button>`;
  container.appendChild(t);
  setTimeout(() => t.style.opacity = '0', duration);
  setTimeout(() => t.remove(), duration + 300);
}

// ── Confirm Dialog ───────────────────────────────────────────────────
function confirmDialog(title, msg, onConfirm, danger = true) {
  let overlay = document.getElementById('confirmOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'confirmOverlay';
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
      <div class="confirm-box">
        <div class="confirm-icon" id="confirmIcon">⚠️</div>
        <div class="confirm-title" id="confirmTitle"></div>
        <div class="confirm-msg" id="confirmMsg"></div>
        <div class="confirm-actions">
          <button class="btn btn-secondary" id="confirmCancel">Batal</button>
          <button class="btn" id="confirmOk">Ya, Lanjutkan</button>
        </div>
      </div>`;
    document.body.appendChild(overlay);
  }
  overlay.querySelector('#confirmTitle').textContent = title;
  // Support HTML di pesan konfirmasi (untuk pesan hapus dengan format bold dll)
  overlay.querySelector('#confirmMsg').innerHTML = msg;
  const okBtn = overlay.querySelector('#confirmOk');
  okBtn.className = danger ? 'btn btn-danger' : 'btn btn-primary';
  overlay.classList.add('open');

  const close = () => overlay.classList.remove('open');
  overlay.querySelector('#confirmCancel').onclick = close;
  overlay.onclick = e => { if (e.target === overlay) close(); };
  okBtn.onclick = () => { close(); onConfirm(); };
}

// ── Global Upload Lock Management ─────────────────────────────────────
window.__isUploadLocked = false;
window.__uploadLockReason = '';

function _onBeforeUnloadPrevent(e) {
  if (window.__isUploadLocked) {
    e.preventDefault();
    e.returnValue = window.__uploadLockReason || 'Upload sedang berjalan! Jangan menutup atau memuat ulang halaman.';
    return e.returnValue;
  }
}

function setUploadLock(locked, reason) {
  window.__isUploadLocked = Boolean(locked);
  window.__uploadLockReason = reason || 'Proses upload sedang berjalan. Dilarang menutup, menyimpan, atau berpindah menu!';

  if (window.__isUploadLocked) {
    window.addEventListener('beforeunload', _onBeforeUnloadPrevent);
    document.body.classList.add('upload-locked');
  } else {
    window.removeEventListener('beforeunload', _onBeforeUnloadPrevent);
    document.body.classList.remove('upload-locked');
  }
}

// ── Modal Management ─────────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  if (window.__isUploadLocked) {
    if (typeof toast === 'function') {
      toast('Upload Berjalan', window.__uploadLockReason || 'Proses upload sedang berlangsung. Dilarang menutup modal!', 'error');
    } else {
      alert(window.__uploadLockReason || 'Proses upload sedang berlangsung. Dilarang menutup modal!');
    }
    return false;
  }
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
  return true;
}

// Prevent closing modal or navigating away during active upload
document.addEventListener('click', e => {
  if (window.__isUploadLocked) {
    // Cegah klik overlay modal
    if (e.target.classList.contains('modal-overlay')) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof cancelAlbumForm === 'function' && window.__albumUploadDoneUnsaved) {
        cancelAlbumForm();
        return;
      }
      closeModal(e.target.id);
      return;
    }
    // Cegah tombol close modal
    if (e.target.closest('.modal-close')) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof cancelAlbumForm === 'function' && window.__albumUploadDoneUnsaved) {
        cancelAlbumForm();
        return;
      }
      if (typeof toast === 'function') {
        toast('Upload Berjalan', window.__uploadLockReason || 'Proses upload sedang berlangsung. Dilarang menutup modal!', 'error');
      }
      return;
    }
    // Cegah klik link navigasi (sidebar / link eksternal / ganti halaman)
    const link = e.target.closest('a');
    if (link && link.href && !link.href.startsWith('javascript:') && !link.getAttribute('href')?.startsWith('#')) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof toast === 'function') {
        toast('Peringatan', window.__uploadLockReason || 'Dilarang beralih halaman saat proses upload masih berlangsung / belum disimpan!', 'error');
      } else {
        alert(window.__uploadLockReason || 'Dilarang beralih halaman saat proses upload masih berlangsung / belum disimpan!');
      }
      return;
    }
  } else {
    if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
    if (e.target.classList.contains('modal-close')) {
      const overlay = e.target.closest('.modal-overlay');
      if (overlay) closeModal(overlay.id);
    }
  }
}, true);

// Cegah tombol Escape keyboard saat upload berjalan
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && window.__isUploadLocked) {
    e.preventDefault();
    e.stopPropagation();
    if (typeof toast === 'function') {
      toast('Upload Berjalan', window.__uploadLockReason || 'Proses upload sedang berlangsung. Dilarang menutup!', 'error');
    }
  }
}, true);

// ── Sidebar Toggle (Mobile) ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }
});

// ── AJAX Helpers ─────────────────────────────────────────────────────
async function apiGet(url) {
  try {
    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('apiGet: response bukan JSON dari', url, '\nResponse:', text.substring(0, 500));
      return { error: 'Response server bukan JSON. Cek error_log PHP.' };
    }
  } catch (err) {
    console.error('apiGet fetch error:', err);
    return { error: 'Gagal menghubungi server: ' + err.message };
  }
}

async function apiPost(url, data) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(data),
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      // Response bukan JSON — tampilkan detail untuk diagnosis
      console.error('apiPost: response bukan JSON dari', url);
      console.error('HTTP Status:', res.status, res.statusText);
      console.error('Response body:', text.substring(0, 1000));
      return {
        error: `Server error (HTTP ${res.status}). Cek console untuk detail PHP error.`
      };
    }
  } catch (err) {
    console.error('apiPost fetch error:', err);
    return { error: 'Gagal menghubungi server: ' + err.message };
  }
}

async function apiDelete(url, data = {}) {
  try {
    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(data),
    });
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch {
      console.error('apiDelete: response bukan JSON dari', url, '\nResponse:', text.substring(0, 500));
      return { error: 'Response server bukan JSON.' };
    }
  } catch (err) {
    console.error('apiDelete fetch error:', err);
    return { error: 'Gagal menghubungi server: ' + err.message };
  }
}

// ── HTML escape ──────────────────────────────────────────────────────
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Table Search / Filter ────────────────────────────────────────────
function initSearch(inputId, tableId, colIndex = -1) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(tr => {
      const text = colIndex >= 0
        ? (tr.cells[colIndex]?.textContent || '')
        : tr.textContent;
      tr.style.display = text.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ── Pagination ───────────────────────────────────────────────────────
// Aman dipanggil BERULANG KALI (misalnya tiap kali tabel/list di-render ulang
// setelah search/filter/tambah/edit/hapus data) — otomatis membersihkan
// paginator lama sebelum membuat yang baru, dan selalu kembali ke halaman 1.
//
// containerId  — id elemen <table> (default) ATAU elemen kontainer lain
//                (mis. <div id="cardList"> untuk tampilan kartu mobile).
// itemSelector — opsional. Default 'tbody tr' kalau containerId adalah
//                <table>, atau ':scope > *' (anak langsung) untuk kontainer
//                lain. Isi manual kalau strukturnya berbeda,
//                mis. initPagination('cardList', 20, ':scope > .art-card').
function initPagination(containerId, pageSize = 20, itemSelector = null) {
  const container = document.getElementById(containerId);
  if (!container) return;

  // Bersihkan paginator lama milik kontainer ini supaya tidak menumpuk.
  document.querySelectorAll('.pagination[data-for="' + containerId + '"]').forEach(el => el.remove());

  const selector = itemSelector || (container.tagName === 'TABLE' ? 'tbody tr' : ':scope > *');
  const items = Array.from(container.querySelectorAll(selector));
  const total = items.length;
  if (total <= pageSize) { items.forEach(it => { it.style.display = ''; }); return; }

  const pages     = Math.ceil(total / pageSize);
  let   current   = 1;
  const paginator = document.createElement('div');
  paginator.className   = 'pagination';
  paginator.dataset.for = containerId;
  // Tabel: taruh setelah .table-wrapper (supaya tetap ikut disembunyikan
  // kalau .table-wrapper/table di-hide). Kontainer non-tabel (grid kartu dsb):
  // taruh setelah kontainer itu SENDIRI (bukan setelah parent-nya) —
  // supaya kalau kontainer ini disembunyikan (mis. ganti folder/tab),
  // paginator ikut tersembunyi juga karena masih sibling di dalam parent
  // yang sama, bukan "bocor" ke luar ke parent yang lebih atas.
  (container.closest('.table-wrapper') || container).after(paginator);

  function renderPage(p) {
    current = p;
    items.forEach((it, i) => {
      it.style.display = (i >= (p - 1) * pageSize && i < p * pageSize) ? '' : 'none';
    });
    paginator.innerHTML = '';
    const mkBtn = (label, page, active = false) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'page-btn' + (active ? ' active' : '');
      b.textContent = label;
      b.onclick = () => renderPage(page);
      return b;
    };
    if (current > 1) paginator.appendChild(mkBtn('‹', current - 1));
    for (let i = 1; i <= pages; i++) {
      if (i === 1 || i === pages || Math.abs(i - current) <= 2) {
        paginator.appendChild(mkBtn(i, i, i === current));
      } else if (Math.abs(i - current) === 3) {
        const sp = document.createElement('span');
        sp.className = 'page-info'; sp.textContent = '…';
        paginator.appendChild(sp);
      }
    }
    if (current < pages) paginator.appendChild(mkBtn('›', current + 1));

    const info = document.createElement('span');
    info.className = 'page-info';
    info.style.marginLeft = '10px';
    info.textContent = total + ' data · hal ' + current + '/' + pages;
    paginator.appendChild(info);
  }
  renderPage(1);
}

// ── Permission Checkboxes ────────────────────────────────────────────
function selectAllPerms(checked) {
  document.querySelectorAll('.perm-item input[type=checkbox]').forEach(cb => cb.checked = checked);
}

// ── Set Button Loading State ─────────────────────────────────────────
function btnLoading(btn, loading = true, originalText = '') {
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle"></span> Menyimpan...';
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalText || originalText;
  }
}

// ── Copy to Clipboard ────────────────────────────────────────────────
function copyText(text) {
  navigator.clipboard.writeText(text)
    .then(() => toast('Disalin!', text, 'success', 1800))
    .catch(() => toast('Gagal menyalin', '', 'error'));
}