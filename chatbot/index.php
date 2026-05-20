<?php
/**
 * index.php — Drop-in component chatbot hybrid Paroki SMDTBA
 *
 * CARA PAKAI:
 *   Ganti baris <?php include 'components/chatbot.php'; ?>
 *   dengan      <?php include 'chatbot/index.php'; ?>
 *
 * File ini hanya output HTML + CSS + JS.
 * Logic AI ada di chatbot/api/chatbot.php (dipanggil via fetch).
 */
?>

<!-- ── CSS ── -->
<link rel="stylesheet" href="/chatbot/assets/chatbot.css">

<!-- ── MARKUP ── -->
<div id="cb-bubble" role="region" aria-label="Chatbot Paroki">

  <!-- Panel chat -->
  <div id="cb-panel" aria-live="polite" aria-hidden="true">

    <!-- Header -->
    <div id="cb-header">
      <div id="cb-avatar">
        <img src="/img/avatar.webp"
             alt="Avatar Paroki SMDTBA"
             onerror="this.style.display='none'">
      </div>
      <div id="cb-header-info">
        <div id="cb-header-name">Asisten Paroki SMDTBA</div>
        <div id="cb-header-status">Online &mdash; siap membantu</div>
      </div>
    </div>

    <!-- Pesan -->
    <div id="cb-messages"></div>

    <!-- Typing indicator -->
    <div id="cb-typing" class="cb-msg bot">
      <div class="cb-msg-dot" style="overflow:hidden;">
        <img src="/img/avatar.webp"
             alt="" style="width:26px;height:26px;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none'">
      </div>
      <div class="cb-bubble-text bot">
        <div class="cb-dot-anim"><span></span><span></span><span></span></div>
      </div>
    </div>

    <!-- Quick replies -->
    <div id="cb-quick"></div>

    <!-- Input -->
    <div id="cb-inputrow">
      <input id="cb-input" type="text" placeholder="Ketik pertanyaan..."
             maxlength="500" aria-label="Tulis pesan" autocomplete="off">
      <button id="cb-send" aria-label="Kirim">
        <svg viewBox="0 0 24 24" fill="none" stroke="#1a1208" stroke-width="2.2"
             stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Tombol bubble -->
  <button id="cb-btn" aria-label="Buka chatbot paroki" aria-expanded="false">
    <div id="cb-badge">1</div>
    <svg class="cb-ico-chat" viewBox="0 0 24 24" fill="none"
         stroke="#c9a96e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <svg class="cb-ico-close" viewBox="0 0 24 24" fill="none"
         stroke="#c9a96e" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
    </svg>
  </button>
</div>

<!-- ── JS ── -->
<script src="/chatbot/assets/chatbot.js"></script>
