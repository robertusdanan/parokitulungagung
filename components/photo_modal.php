<?php
/**
 * components/photo_modal.php
 * Modal foto yang dipakai di index.php, content.php, kelompok.php
 * Letakkan di dalam #outer-wrapper, sebelum </div> penutupnya.
 */
?>
<div class="modalx" id="boxModal" role="dialog" aria-modal="true" aria-label="Foto">
  <div class="photo-modal-content">
    <div class="photo-modal-hero">
      <img id="boxModalImage" src="/img/smdtba-sharelogo.webp" alt="" width="192" height="192">
      <button class="photo-modal-close" id="boxModalClose" aria-label="Tutup">&times;</button>
      <div class="photo-modal-overlay"></div>
    </div>
    <div class="photo-modal-info">
      <div class="photo-modal-badge" id="boxModalText"></div>
      <h2  class="photo-modal-name"  id="boxModalTitle"></h2>
      <div class="photo-modal-sub"   id="boxModalSubText"></div>
    </div>
  </div>
</div>
