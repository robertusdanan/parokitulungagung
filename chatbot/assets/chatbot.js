/**
 * chatbot.js — Client-side hybrid chatbot
 * Paroki SMDTBA Tulungagung
 *
 * Fitur:
 * - Kirim pesan ke API endpoint PHP
 * - Typing animation
 * - Memory (session_id persisten di localStorage)
 * - Konteks halaman aktif otomatis
 * - Anti-spam UI
 * - Render link & quick replies dari API
 */

(function () {
  'use strict';

  /* ── CONFIG ─────────────────────────────────────────────── */
  const API_ENDPOINT  = '/chatbot/api/chatbot.php'; // sesuaikan path
  const TYPING_DELAY_MIN = 600;
  const TYPING_DELAY_MAX = 1400;
  const INITIAL_QR    = ['Jadwal Misa', 'Lokasi Gereja', 'Kontak Paroki'];
  const AVATAR_URL    = '/img/avatar.webp';
  const STORAGE_KEY   = 'cb_session_id';

  /* ── SESSION ID ─────────────────────────────────────────── */
  function getSessionId() {
    let sid = localStorage.getItem(STORAGE_KEY);
    if (!sid) {
      sid = 'web_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
      localStorage.setItem(STORAGE_KEY, sid);
    }
    return sid;
  }

  /* ── KONTEKS HALAMAN ────────────────────────────────────── */
  function getPageContext() {
    return {
      page_url:   window.location.href,
      page_title: document.title || ''
    };
  }

  /* ── DOM HELPERS ────────────────────────────────────────── */
  const $ = id => document.getElementById(id);

  function buildBubble(text, links, isUser, source) {
    let html = text.replace(/\n/g, '<br>');

    if (links && links.length) {
      html += '<br>';
      links.forEach(l => {
        const target = l.external ? ' target="_blank" rel="noopener noreferrer"' : '';
        html += `<br><a href="${l.url}"${target}>${l.text} →</a>`;
      });
    }

    // Badge sumber AI (hanya untuk jawaban bot dari Gemini)
    let sourceBadge = '';
    if (!isUser && source === 'gemini') {
      sourceBadge = '<span class="cb-ai-badge">✨ AI</span>';
    }

    const dot = isUser ? '' :
      `<div class="cb-msg-dot" style="overflow:hidden;" aria-hidden="true">
         <img src="${AVATAR_URL}" alt="" style="width:26px;height:26px;object-fit:cover;border-radius:50%;"
              onerror="this.style.display='none'">
       </div>`;

    return `<div class="cb-msg ${isUser ? 'user' : 'bot'}">
              ${dot}
              <div class="cb-bubble-text">${html}${sourceBadge}</div>
            </div>`;
  }

  function appendMsg(html) {
    const m    = $('cb-messages');
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    m.appendChild(wrap.firstChild);
    scrollBottom();
  }

  function scrollBottom() {
    const m = $('cb-messages');
    m.scrollTop = m.scrollHeight;
  }

  function showTyping() {
    $('cb-typing').classList.add('show');
    scrollBottom();
  }

  function hideTyping() {
    $('cb-typing').classList.remove('show');
  }

  function setQuickReplies(qr) {
    const el = $('cb-quick');
    el.innerHTML = '';
    if (!qr || !qr.length) return;
    qr.forEach(q => {
      const btn = document.createElement('button');
      btn.className = 'cb-qr';
      btn.textContent = q;
      btn.addEventListener('click', () => sendMsg(q));
      el.appendChild(btn);
    });
  }

  function disableInput(disabled) {
    $('cb-input').disabled = disabled;
    $('cb-send').disabled  = disabled;
  }

  /* ── KIRIM PESAN ────────────────────────────────────────── */
  async function sendMsg(text) {
    text = (text || '').trim();
    if (!text) return;

    // Tampilkan pesan user
    appendMsg(buildBubble(text, null, true, null));
    $('cb-input').value = '';
    $('cb-quick').innerHTML = '';
    disableInput(true);

    // Typing delay realistis
    const delay = TYPING_DELAY_MIN + Math.random() * (TYPING_DELAY_MAX - TYPING_DELAY_MIN);
    showTyping();

    try {
      const response = await fetch(API_ENDPOINT, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message:    text,
          session_id: getSessionId(),
          ...getPageContext()
        })
      });

      // Minimal delay agar typing terasa natural
      await new Promise(r => setTimeout(r, Math.max(0, delay - (Date.now() % delay))));
      hideTyping();

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();

      if (data.error) {
        throw new Error(data.error);
      }

      appendMsg(buildBubble(data.answer, data.links || [], false, data.source));
      setQuickReplies(data.quick_replies || []);

    } catch (err) {
      console.warn('[Chatbot]', err);
      hideTyping();
      appendMsg(buildBubble(
        'Maaf, terjadi gangguan sementara. 🙏<br>Silakan coba lagi atau hubungi <a href="/kontak">sekretariat paroki</a>.',
        [], false, 'error'
      ));
      setQuickReplies(['Jadwal Misa', 'Kontak Paroki']);
    } finally {
      disableInput(false);
      setTimeout(() => $('cb-input').focus(), 100);
    }
  }

  /* ── INISIALISASI ───────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    const btn   = $('cb-btn');
    const panel = $('cb-panel');
    const input = $('cb-input');
    const send  = $('cb-send');
    const badge = $('cb-badge');

    let isOpen  = false;
    let greeted = false;

    function openPanel() {
      isOpen = true;
      btn.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
      panel.classList.add('open');
      panel.setAttribute('aria-hidden', 'false');
      badge.style.opacity = '0';

      if (!greeted) {
        greeted = true;
        showTyping();
        setTimeout(() => {
          hideTyping();
          appendMsg(buildBubble(
            'Halo! Selamat datang di Paroki SMDTBA Tulungagung. 🙏\n\nSaya siap membantu Anda. Silakan pilih topik atau ketik pertanyaan Anda:',
            null, false, 'kb'
          ));
          setQuickReplies(INITIAL_QR);
        }, 700);
      }

      setTimeout(() => input.focus(), 350);
    }

    function closePanel() {
      isOpen = false;
      btn.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      panel.classList.remove('open');
      panel.setAttribute('aria-hidden', 'true');
    }

    btn.addEventListener('click', () => isOpen ? closePanel() : openPanel());
    send.addEventListener('click', () => sendMsg(input.value.trim()));
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMsg(input.value.trim());
      }
    });

    // Pulse badge setelah 8 detik jika belum dibuka
    setTimeout(() => {
      if (!greeted && !isOpen) {
        badge.style.animation = 'cbPulse .6s ease 3';
      }
    }, 8000);
  });

})();
