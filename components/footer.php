<?php ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Cinzel:wght@400;500;600&family=DM+Sans:wght@300;400&display=swap');

/* ================================================================
   FOOTER — Paroki Tulungagung
   ft = prefix namespace
   ================================================================ */

:root {
  --ft-gold-bright:  #f0d98a;
  --ft-gold-mid:     #dab85a;
  --ft-gold-deep:    #c9a84c;
  --ft-gold-dim:     rgba(218,175,90,0.5);
  --ft-gold-faint:   rgba(218,175,90,0.1);
  --ft-gold-ghost:   rgba(218,175,90,0.06);
  --ft-cream:        #ede3d0;
  --ft-cream-dim:    rgba(237,227,208,0.55);
  --ft-cream-ghost:  rgba(237,227,208,0.25);
  --ft-bg:           #080610;
  --ft-bg-card:      #0e0b18;
  --ft-line:         rgba(218,175,90,0.18);
}

/* ── Wrapper utama ─────────────────────────────────────────────── */
.ft-wrap {
  background: var(--ft-bg);
  color: var(--ft-cream);
  font-family: 'DM Sans', sans-serif;
  font-weight: 300;
  position: relative;
  overflow: hidden;
}

/* Noise texture */
.ft-wrap::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
  background-size: 200px 200px;
  opacity: 0.022;
  pointer-events: none;
  z-index: 0;
}

/* Ambient glow tengah */
.ft-ambient {
  position: absolute;
  width: 700px;
  height: 400px;
  left: 50%;
  top: 0;
  transform: translateX(-50%);
  background: radial-gradient(ellipse, rgba(218,175,90,0.055) 0%, transparent 65%);
  pointer-events: none;
  z-index: 0;
}

/* ── Garis emas tipis paling atas ──────────────────────────────── */
.ft-topline {
  position: relative;
  z-index: 1;
  height: 1px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(218,175,90,0.15) 15%,
    rgba(218,175,90,0.6) 35%,
    var(--ft-gold-bright) 50%,
    rgba(218,175,90,0.6) 65%,
    rgba(218,175,90,0.15) 85%,
    transparent 100%
  );
}

/* ── Header compact ────────────────────────────────────────────── */
.ft-header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  max-width: 960px;
  margin: 0 auto;
  padding: 5px 50px 5px;
  border-bottom: 1px solid var(--ft-line);
}

/* Identitas kiri */
.ft-header-identity {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.ft-header-cross {
  color: var(--ft-gold-mid);
  filter: drop-shadow(0 0 4px rgba(218,175,90,0.45));
  flex-shrink: 0;
}

.ft-header-text {}

.ft-header-name {
  font-family: 'Cormorant Garamond', serif;
  font-weight: 300;
  font-style: italic;
  font-size: 1.15rem; /* dikecilkan dari 1.35rem */
  letter-spacing: 0.04em;
  margin: 0;
  line-height: 1.1;
  background: linear-gradient(135deg,
    var(--ft-gold-deep) 0%,
    var(--ft-gold-bright) 45%,
    #ffe9a0 50%,
    var(--ft-gold-bright) 55%,
    var(--ft-gold-deep) 100%
  );
  background-size: 200% auto;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.ft-header-sub {
  font-family: 'Cinzel', serif;
  font-size: 0.42rem; /* dikecilkan dari 0.48rem */
  letter-spacing: 0.28em;
  text-transform: uppercase;
  color: rgba(218,175,90,0.38);
  margin: 3px 0 0;
  font-weight: 400;
}

/* Kutipan kanan — inline compact */
.ft-header-quote {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 0.75rem; /* dikecilkan dari 0.82rem */
  font-weight: 300;
  color: rgba(237,227,208,0.22);
  letter-spacing: 0.02em;
  line-height: 1.6;
  text-align: right;
  border-left: 1px solid var(--ft-line);
  padding-left: 20px;
  max-width: 320px;
}

/* ── Grid tiga kolom ───────────────────────────────────────────── */
.ft-grid {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  max-width: 960px;
  margin: 0 auto;
}

.ft-col {
  padding: 8px 24px 4px; /* dikurangi dari 10px 28px 5px */
  border-right: 1px solid var(--ft-line);
}
.ft-col:last-child {
  border-right: none;
}

/* Judul kolom */
.ft-col-title {
  font-family: 'Cinzel', serif;
  font-size: 0.48rem; /* dikecilkan dari 0.55rem */
  font-weight: 500;
  letter-spacing: 0.32em;
  text-transform: uppercase;
  color: var(--ft-gold-deep);
  margin: 0 0 16px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.ft-col-title::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, var(--ft-gold-faint), transparent);
}

/* ── Navigasi ──────────────────────────────────────────────────── */
.ft-nav {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ft-nav li a {
  display: flex;
  align-items: center;
  gap: 8px;
  color: rgba(237,227,208,0.4);
  text-decoration: none;
  font-size: 0.75rem; /* dikecilkan dari 0.82rem */
  font-weight: 300;
  letter-spacing: 0.04em;
  padding: 4px 0;
  transition: color 0.25s, gap 0.25s;
  position: relative;
}
.ft-nav li a::before {
  content: '';
  width: 0;
  height: 1px;
  background: var(--ft-gold-mid);
  transition: width 0.25s;
  flex-shrink: 0;
}
.ft-nav li a:hover {
  color: var(--ft-cream);
  gap: 12px;
}
.ft-nav li a:hover::before {
  width: 14px;
}

/* ── Jadwal Misa ───────────────────────────────────────────────── */
.ft-jadwal-item {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 4px 0; /* dikurangi dari 6px 0 */
  border-bottom: 1px solid rgba(218,175,90,0.08);
}
.ft-jadwal-item:last-of-type {
  border-bottom: none;
}
.ft-jadwal-hari {
  font-size: 0.72rem; /* dikecilkan dari 0.8rem */
  font-weight: 300;
  color: rgba(237,227,208,0.45);
  letter-spacing: 0.02em;
}
.ft-jadwal-waktu {
  font-family: 'Cormorant Garamond', serif;
  font-size: 0.95rem; /* dikecilkan dari 1.05rem */
  font-weight: 400;
  color: var(--ft-gold-mid);
  letter-spacing: 0.05em;
}

/* Tombol kecil Selengkapnya */
.ft-btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: transparent;
  border: 1px solid rgba(218,175,90,0.22);
  border-radius: 100px;
  padding: 5px 12px;
  color: var(--ft-gold-dim);
  font-family: 'Cinzel', serif;
  font-size: 0.46rem; /* dikecilkan dari 0.52rem */
  font-weight: 400;
  letter-spacing: 0.25em;
  text-transform: uppercase;
  cursor: pointer;
  text-decoration: none;
  margin-top: 14px;
  transition: border-color 0.25s, color 0.25s, background 0.25s;
}
.ft-btn-ghost svg {
  width: 11px; height: 11px;
  stroke: currentColor; fill: none;
  stroke-width: 1.5;
  flex-shrink: 0;
}
.ft-btn-ghost:hover {
  border-color: var(--ft-gold-mid);
  color: var(--ft-gold-bright);
  background: var(--ft-gold-ghost);
}

/* ── Info kontak ───────────────────────────────────────────────── */
.ft-info-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 10px; /* dikurangi dari 14px */
}
.ft-info-icon {
  flex-shrink: 0;
  width: 14px;
  height: 14px;
  stroke: var(--ft-gold-mid);
  fill: none;
  stroke-width: 1.5;
  stroke-linecap: round;
  stroke-linejoin: round;
  opacity: 0.75;
  margin-top: 2px;
}
.ft-info-text {
  font-size: 0.72rem; /* dikecilkan dari 0.8rem */
  font-weight: 300;
  color: rgba(237,227,208,0.45);
  line-height: 1.6;
  letter-spacing: 0.02em;
}

/* Separator tipis dalam kolom */
.ft-col-sep {
  height: 1px;
  background: linear-gradient(90deg, var(--ft-gold-faint), transparent);
  margin: 14px 0;
}

/* ── Bottom bar ────────────────────────────────────────────────── */
.ft-bottom {
  position: relative;
  z-index: 1;
  border-top: 1px solid var(--ft-line);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 5px 40px 5px;
  max-width: 960px;
  margin: 0 auto;
}

/* Copyright */
.ft-copy {
  font-size: 0.62rem; /* dikecilkan dari 0.68rem */
  font-weight: 300;
  color: rgba(237,227,208,0.2);
  letter-spacing: 0.06em;
}

/* Sosmed */
.ft-social {
  display: flex;
  gap: 10px;
  align-items: center;
}

.ft-social-btn {
  width: 32px;
  height: 32px;
  border: 1px solid rgba(218,175,90,0.18);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: border-color 0.25s, background 0.25s, transform 0.25s;
}
.ft-social-btn svg {
  width: 13px; height: 13px;
  stroke: var(--ft-gold-mid); fill: none;
  stroke-width: 1.5;
  transition: stroke 0.25s;
}
.ft-social-btn:hover {
  border-color: var(--ft-gold-mid);
  background: var(--ft-gold-ghost);
  transform: translateY(-2px);
}
.ft-social-btn:hover svg { stroke: var(--ft-gold-bright); }

/* ══════════════════════════════════════════════════════════════
   MODAL
   ══════════════════════════════════════════════════════════════ */
.ft-modal-backdrop {
  position: fixed; inset: 0; z-index: 9000;
  background: rgba(6, 4, 10, 0.88);
  backdrop-filter: blur(18px) saturate(1.2);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none;
  transition: opacity 0.38s ease;
}
.ft-modal-backdrop.open {
  opacity: 1; pointer-events: all;
}

.ft-modal-card {
  background: linear-gradient(160deg, #0e0b18 0%, #080610 100%);
  border: 1px solid rgba(218,175,90,0.22);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 48px 96px rgba(0,0,0,0.7), 0 0 0 1px rgba(218,175,90,0.06);
  transform: scale(0.94) translateY(16px);
  transition: transform 0.38s cubic-bezier(0.22, 0.61, 0.36, 1);
  width: 360px; max-width: calc(100vw - 40px);
}
.ft-modal-card--wide { width: 420px; }
.ft-modal-backdrop.open .ft-modal-card {
  transform: scale(1) translateY(0);
}

.ft-modal-header {
  padding: 28px 28px 20px;
  text-align: center;
  border-bottom: 1px solid rgba(218,175,90,0.12);
  position: relative;
  background: linear-gradient(180deg, rgba(218,175,90,0.05) 0%, transparent 100%);
}

.ft-modal-close {
  position: absolute; top: 14px; right: 16px;
  background: rgba(218,175,90,0.06);
  border: 1px solid rgba(218,175,90,0.15);
  border-radius: 50%; width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  color: rgba(237,227,208,0.4); font-size: 16px;
  cursor: pointer; line-height: 1;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.ft-modal-close:hover {
  background: rgba(218,175,90,0.14); color: var(--ft-cream);
  border-color: rgba(218,175,90,0.35);
}

.ft-modal-logo {
  margin: 0 auto 14px; display: block;
  filter: drop-shadow(0 0 12px rgba(218,175,90,0.2));
}

.ft-modal-title {
  font-family: 'Cormorant Garamond', serif;
  font-style: italic;
  font-size: 1.1rem; font-weight: 300;
  color: var(--ft-cream); letter-spacing: 0.04em;
  margin: 0 0 4px;
}
.ft-modal-subtitle {
  font-family: 'Cinzel', serif;
  font-size: 0.52rem; font-weight: 400;
  color: rgba(218,175,90,0.45); letter-spacing: 0.3em;
  text-transform: uppercase; margin: 0;
}

.ft-modal-body {
  padding: 22px 28px 26px;
}

.ft-modal-label {
  font-family: 'Cinzel', serif;
  font-size: 0.5rem; letter-spacing: 0.35em; text-transform: uppercase;
  color: rgba(218,175,90,0.45); font-weight: 400;
  margin-bottom: 10px; display: block;
}

.ft-modal-email {
  font-family: 'Cormorant Garamond', serif;
  font-size: 0.9rem; color: var(--ft-cream-dim);
  font-style: italic; letter-spacing: 0.03em;
  margin-bottom: 12px;
}
.ft-modal-dev {
  font-size: 0.78rem; color: rgba(237,227,208,0.4); font-weight: 300;
}
.ft-modal-dev a {
  color: var(--ft-gold-mid); font-weight: 400; text-decoration: none;
  border-bottom: 1px solid rgba(218,175,90,0.25); padding-bottom: 1px;
  transition: color 0.2s, border-color 0.2s;
}
.ft-modal-dev a:hover { color: var(--ft-cream); border-color: var(--ft-cream); }

.ft-modal-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, rgba(218,175,90,0.2), transparent);
  margin: 18px 0;
}

/* Jadwal rows dalam modal */
.ft-modal-jadwal-item {
  display: flex; justify-content: space-between; align-items: baseline;
  padding: 8px 0; border-bottom: 1px solid rgba(218,175,90,0.08);
}
.ft-modal-jadwal-item:last-child { border-bottom: none; }
.ft-modal-jadwal-hari {
  font-size: 0.8rem; font-weight: 300;
  color: rgba(237,227,208,0.45); letter-spacing: 0.02em; flex: 1;
}
.ft-modal-jadwal-waktu {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1rem; font-weight: 400;
  color: var(--ft-gold-mid); text-align: right; flex-shrink: 0; margin-left: 12px;
}

/* Stasi rows */
.ft-modal-stasi-item {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 9px 0; border-bottom: 1px solid rgba(218,175,90,0.07);
}
.ft-modal-stasi-item:last-child { border-bottom: none; }
.ft-modal-stasi-nama {
  font-size: 0.78rem; font-weight: 300;
  color: rgba(237,227,208,0.42); letter-spacing: 0.02em; flex: 1; line-height: 1.5;
}
.ft-modal-stasi-waktu {
  font-family: 'Cormorant Garamond', serif;
  font-size: 0.9rem; font-weight: 400;
  color: var(--ft-gold-mid); text-align: right;
  flex-shrink: 0; margin-left: 12px; line-height: 1.5;
}

/* Catatan bawah modal */
.ft-modal-note {
  display: flex; align-items: center; gap: 7px;
  margin-top: 16px; padding-top: 14px;
  border-top: 1px solid rgba(218,175,90,0.1);
  font-size: 0.7rem; font-weight: 300;
  color: rgba(237,227,208,0.28); letter-spacing: 0.02em;
}
.ft-modal-note svg {
  width: 12px; height: 12px; flex-shrink: 0;
  stroke: var(--ft-gold-mid); fill: none; stroke-width: 2;
  stroke-linecap: round; stroke-linejoin: round; opacity: 0.45;
}

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 680px) {
  .ft-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 20px 14px;
  }
  .ft-header-quote {
    border-left: none;
    border-top: 1px solid var(--ft-line);
    padding-left: 0;
    padding-top: 12px;
    text-align: left;
    max-width: 100%;
  }
  .ft-grid {
    grid-template-columns: 1fr 1fr;
  }
  .ft-col:nth-child(1) {
    grid-column: 1 / -1;
    border-right: none;
    border-bottom: 1px solid var(--ft-line);
    padding-bottom: 14px;
  }
  .ft-col:nth-child(2) {
    border-bottom: 1px solid var(--ft-line);
    padding-bottom: 14px;
  }
  .ft-col:nth-child(3) {
    border-right: none;
    border-bottom: 1px solid var(--ft-line);
    padding-bottom: 14px;
  }
  .ft-bottom { padding: 12px 20px 14px; flex-direction: column; align-items: center; text-align: center; }

  /* Navigasi 2 kolom di mobile */
  .ft-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 16px;
  }
}

@media (max-width: 420px) {
  .ft-grid { grid-template-columns: 1fr; }
  .ft-col { border-right: none; border-bottom: 1px solid var(--ft-line); }
  .ft-col:last-child { border-bottom: none; }

  /* Tetap 2 kolom untuk navigasi di layar sangat kecil */
  .ft-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 16px;
  }
}
</style>


<!-- ══════════════════════════════════════════
     MODAL: Team Web
     ══════════════════════════════════════════ -->
<div class="ft-modal-backdrop" id="footerModalTeam" onclick="ftModalClose('footerModalTeam', event)">
  <div class="ft-modal-card">
    <div class="ft-modal-header">
      <button class="ft-modal-close" onclick="document.getElementById('footerModalTeam').classList.remove('open')" aria-label="Tutup">&#215;</button>
      <img class="ft-modal-logo" src="/img/smdtba-sharelogo.webp" alt="SMDTBA Logo" width="192" height="192">
      <p class="ft-modal-title">Team Web SMDTBA</p>
      <p class="ft-modal-subtitle">Paroki Tulungagung</p>
    </div>
    <div class="ft-modal-body">
      <span class="ft-modal-label">Kontak &amp; Dukungan</span>
      <p class="ft-modal-email">support@parokitulungagung.org</p>
      <p class="ft-modal-dev">Dikembangkan oleh <a href="/redirect.php" target="_blank">Tenaga IT Paroki Tulungagung</a></p>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     MODAL: Jadwal Misa Lengkap
     ══════════════════════════════════════════ -->
<div class="ft-modal-backdrop" id="footerModalJadwal" onclick="ftModalClose('footerModalJadwal', event)">
  <div class="ft-modal-card ft-modal-card--wide">

    <div class="ft-modal-header">
      <button class="ft-modal-close" onclick="document.getElementById('footerModalJadwal').classList.remove('open')" aria-label="Tutup">&#215;</button>
      <div style="width:40px;height:40px;border:1px solid rgba(218,175,90,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;background:rgba(218,175,90,0.06);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--ft-gold-mid)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2"/>
          <line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/>
          <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
      </div>
      <p class="ft-modal-title">Jadwal Misa Lengkap</p>
      <p class="ft-modal-subtitle">Paroki Tulungagung</p>
    </div>

    <div class="ft-modal-body">

      <span class="ft-modal-label">Misa Paroki</span>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Senin &ndash; Rabu</span><span class="ft-modal-jadwal-waktu">05.30</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Kamis <small style="font-size:11px;opacity:0.55;">(Susteran)</small></span><span class="ft-modal-jadwal-waktu">05.30</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Jumat</span><span class="ft-modal-jadwal-waktu">18.00</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Sabtu</span><span class="ft-modal-jadwal-waktu">18.00</span></div>
      <div class="ft-modal-jadwal-item"><span class="ft-modal-jadwal-hari">Minggu</span><span class="ft-modal-jadwal-waktu">07.00</span></div>

      <div class="ft-modal-divider"></div>

      <span class="ft-modal-label">Misa Stasi</span>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Gembala yang Baik Ngunut</span><span class="ft-modal-stasi-waktu">Sabtu 18.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Rejotangan</span><span class="ft-modal-stasi-waktu">Sabtu 16.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Trenggalek</span><span class="ft-modal-stasi-waktu">Minggu 08.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Kalangbret</span><span class="ft-modal-stasi-waktu">Minggu I &amp; II 10.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi St. Maria Dongko</span><span class="ft-modal-stasi-waktu">Minggu I &amp; II 11.00</span></div>
      <div class="ft-modal-stasi-item"><span class="ft-modal-stasi-nama">Stasi Sendang</span><span class="ft-modal-stasi-waktu">Minggu II 11.00</span></div>

      <div class="ft-modal-note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Jadwal dapat berubah pada hari raya &amp; liturgi khusus
      </div>

    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════
     FOOTER
     ══════════════════════════════════════════ -->
<footer class="ft-wrap">

  <div class="ft-ambient" aria-hidden="true"></div>

  <!-- Garis emas teratas -->
  <div class="ft-topline" aria-hidden="true"></div>

  <!-- Header compact: identitas + kutipan dalam satu baris -->
  <div class="ft-header">
    <div class="ft-header-identity">
      <span class="ft-header-cross" aria-hidden="true">
        <svg width="11" height="16" viewBox="0 0 11 16" fill="none">
          <rect x="4" y="0" width="3" height="16" rx="1.5" fill="currentColor"/>
          <rect x="0" y="4" width="11" height="3" rx="1.5" fill="currentColor"/>
        </svg>
      </span>
      <div class="ft-header-text">
        <h2 class="ft-header-name">Gereja Katolik Tulungagung</h2>
        <p class="ft-header-sub">Paroki Santa Maria Dengan Tidak Bernoda Asal &middot; Keuskupan Surabaya</p>
      </div>
    </div>
  </div>

  <!-- Grid konten -->
  <div class="ft-grid">

    <!-- Kolom 1: Navigasi -->
    <div class="ft-col">
      <p class="ft-col-title">Navigasi</p>
      <ul class="ft-nav">
        <li><a href="/">Beranda</a></li>
        <li><a href="/tentang">Tentang</a></li>
        <li><a href="/agenda">Info &amp; Agenda</a></li>
        <li><a href="/jadwal-misa">Jadwal Misa</a></li>
        <li><a href="/galeri">Galeri Foto</a></li>
        <li><a href="/kontak">Kontak</a></li>
        <li><a href="/kebijakan-privasi">Kebijakan Privasi</a></li>
        <li><a href="/kebijakan-cookie">Kebijakan Cookie</a></li>
      </ul>
    </div>

    <!-- Kolom 2: Jadwal Misa -->
    <div class="ft-col">
      <p class="ft-col-title">Jadwal Misa</p>

      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Senin &ndash; Rabu</span><span class="ft-jadwal-waktu">05.30</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Kamis (Susteran)</span><span class="ft-jadwal-waktu">05.30</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Jumat</span><span class="ft-jadwal-waktu">18.00</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Sabtu</span><span class="ft-jadwal-waktu">18.00</span></div>
      <div class="ft-jadwal-item"><span class="ft-jadwal-hari">Minggu</span><span class="ft-jadwal-waktu">07.00</span></div>

      <a href="/jadwal-misa" class="ft-btn-ghost" aria-label="Lihat jadwal misa lengkap">
        <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Selengkapnya
      </a>
    </div>

    <!-- Kolom 3: Kontak -->
    <div class="ft-col">
      <p class="ft-col-title">Kontak</p>

      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true">
          <path d="M8 1.5C5.5 1.5 3.5 3.5 3.5 6c0 3.5 4.5 8.5 4.5 8.5S12.5 9.5 12.5 6c0-2.5-2-4.5-4.5-4.5z"/>
          <circle cx="8" cy="6" r="1.5" fill="var(--ft-gold-mid)" stroke="none"/>
        </svg>
        <span class="ft-info-text">Jl. Ahmad Yani Tim. Gg. IV No.1, Bago, Tulungagung</span>
      </div>

      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true">
          <path d="M3 3h2l1 3-1.5 1.5a9 9 0 003 3L9 9l3 1v2a1 1 0 01-1 1C6 13 3 7 3 4a1 1 0 010-1z"/>
        </svg>
        <span class="ft-info-text">(0355) 321727</span>
      </div>

      <div class="ft-info-item">
        <svg class="ft-info-icon" viewBox="0 0 16 16" aria-hidden="true">
          <circle cx="8" cy="8" r="6"/>
          <path d="M8 4v4l2.5 2.5" stroke-linecap="round"/>
        </svg>
        <span class="ft-info-text">Misa Harian &amp; Mingguan</span>
      </div>

      <div class="ft-col-sep"></div>

      <button class="ft-btn-ghost" onclick="document.getElementById('footerModalTeam').classList.add('open')">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Team Web SMDTBA
      </button>
    </div>

  </div>

  <!-- Bottom bar -->
  <div class="ft-bottom">
    <span class="ft-copy">&copy; <?= date('Y') ?> Paroki Santa Maria Dengan Tidak Bernoda Asal Tulungagung</span>

    <div class="ft-social">
      <a href="https://www.facebook.com/SantaMariaDTBA" class="ft-social-btn" target="_blank" rel="noopener" title="Facebook" aria-label="Facebook">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
      </a>
      <a href="https://www.instagram.com/komsosparokitulungagung/" class="ft-social-btn" target="_blank" rel="noopener" title="Instagram" aria-label="Instagram">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="var(--ft-gold-mid)" stroke="none"/></svg>
      </a>
      <a href="https://www.youtube.com/@KomsosParokiTulungagung" class="ft-social-btn" target="_blank" rel="noopener" title="YouTube" aria-label="YouTube">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M10 9l6 3-6 3V9z" fill="var(--ft-gold-mid)" stroke="none"/></svg>
      </a>
    </div>
  </div>

</footer>

<?php include __DIR__ . '/../chatbot/index.php'; ?>


<script>
function ftModalClose(id, e) {
  if (e.target === document.getElementById(id))
    document.getElementById(id).classList.remove('open');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.ft-modal-backdrop.open').forEach(function(el) {
      el.classList.remove('open');
    });
  }
});

// Alias backward compat
function footerModalClose(id, e) { ftModalClose(id, e); }
function ShowAboutBox() {
  document.getElementById('footerModalTeam').classList.add('open');
}
</script>