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

// ── Modal Management ─────────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
  if (e.target.classList.contains('modal-close')) {
    const overlay = e.target.closest('.modal-overlay');
    if (overlay) closeModal(overlay.id);
  }
});

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
function initPagination(tableId, pageSize = 20) {
  const table = document.getElementById(tableId);
  if (!table) return;
  const tbody = table.querySelector('tbody');
  const rows  = Array.from(tbody.querySelectorAll('tr'));
  const total = rows.length;
  if (total <= pageSize) return;

  const pages     = Math.ceil(total / pageSize);
  let   current   = 1;
  const paginator = document.createElement('div');
  paginator.className = 'pagination';
  table.parentElement.after(paginator);

  function renderPage(p) {
    current = p;
    rows.forEach((r, i) => {
      r.style.display = (i >= (p - 1) * pageSize && i < p * pageSize) ? '' : 'none';
    });
    paginator.innerHTML = '';
    const mkBtn = (label, page, active = false) => {
      const b = document.createElement('button');
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