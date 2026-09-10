/* ═══════════════════════════════════════════════════════
   ARTIKEL DETAIL — Page-specific scripts
   Dijalankan hanya di halaman artikel-detail
   ═══════════════════════════════════════════════════════ */
(function () {
  if (!document.querySelector('.art-detail-content')) return; // bukan artikel-detail

  document.addEventListener('DOMContentLoaded', function () {

    // ── Block 1 ──
    const container = document.querySelector('.art-detail-content');
      if (!container) return;
    
      const nodes = Array.from(container.children);
      // groupItems: array of { wrap, caption, parentNode } per group
      let groupItems = [];
    
      nodes.forEach((node, index) => {
        const imgs = node.querySelectorAll ? node.querySelectorAll('img') : [];
        if (imgs.length === 0) return;
    
        imgs.forEach(img => {
          // 1. Bungkus img dalam .img-wrap jika belum
          if (!img.closest('.img-wrap')) {
            const wrap = document.createElement('div');
            wrap.className = 'img-wrap';
            img.parentNode.insertBefore(wrap, img);
            wrap.appendChild(img);
          }
          const imgWrap = img.closest('.img-wrap');
    
          // 2. Kumpulkan caption dari figcaption saudara kandung img-wrap
          const parentEl = imgWrap.parentElement; // biasanya <figure class="img-figure">
          let captionText = '';
          if (parentEl) {
            const figcap = parentEl.querySelector('figcaption');
            if (figcap) captionText = figcap.textContent.trim();
          }
          // Fallback: data-caption pada node
          if (!captionText && node.dataset && node.dataset.caption) {
            captionText = node.dataset.caption;
          }
    
          groupItems.push({ wrap: imgWrap, caption: captionText, parentNode: node });
        });
    
        // 3. Cek apakah node berikutnya juga punya gambar
        const next = nodes[index + 1];
        const nextHasImg = next && next.querySelector && next.querySelector('img');
    
        if (!nextHasImg) {
          // Akhir grup — proses jika ada lebih dari 1 gambar
          if (groupItems.length > 1) {
            const row = document.createElement('div');
            row.className = 'image-row';
    
            // Caption pertama yang tersedia untuk grup
            const groupCaption = (groupItems.find(item => item.caption) || {}).caption || '';
            const seenParents = new Set();
    
            groupItems.forEach(({ wrap, parentNode }) => {
              // Pindahkan figcaption ke dalam img-wrap sebagai elemen SEO (hidden)
              const parentEl = wrap.parentElement;
              if (parentEl && parentEl !== row) {
                const figcap = parentEl.querySelector('figcaption:not(.img-caption-hidden)');
                if (figcap) {
                  figcap.classList.add('img-caption-hidden');
                  wrap.appendChild(figcap); // pindah masuk ke wrap, tersembunyi
                }
              }
              row.appendChild(wrap); // pindahkan wrap ke row
              seenParents.add(parentNode);
            });
    
            // 4. Tambahkan SATU caption gabungan di bawah semua gambar
            if (groupCaption) {
              const cap = document.createElement('div');
              cap.className = 'image-row-caption';
              cap.textContent = groupCaption;
              row.appendChild(cap);
            }
    
            // 5. Sisipkan row setelah node terakhir grup
            node.parentNode.insertBefore(row, node.nextSibling);
    
            // 6. Hapus container gambar yang sekarang sudah kosong (figurenya)
            seenParents.forEach(p => {
              if (p && p.parentNode && p.querySelectorAll('img').length === 0) {
                p.parentNode.removeChild(p);
              }
            });
          }
    
          groupItems = [];
        }
      });

    // ── Block 2 ──
    document.querySelectorAll('.image-row').forEach(row => {
        const wraps = row.querySelectorAll('.img-wrap');
        wraps.forEach(w => {
          const imgs = w.querySelectorAll('img');
          if (imgs.length > 1) {
            imgs.forEach((img, i) => {
              if (i === 0) return;
              const newWrap = document.createElement('div');
              newWrap.className = 'img-wrap';
              w.parentNode.insertBefore(newWrap, w.nextSibling);
              newWrap.appendChild(img);
            });
          }
        });
      });

    // ── Block 3 ──
    document.querySelectorAll('.image-row img').forEach(img => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () {
          const overlay = document.createElement('div');
          overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.9);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:zoom-out;';
          const clone = document.createElement('img');
          clone.src = img.src; clone.alt = img.alt || '';
          clone.style.cssText = 'max-width:90%;max-height:90%;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.5);';
          overlay.appendChild(clone);
          overlay.addEventListener('click', () => overlay.remove());
          document.body.appendChild(overlay);
        });
      });

    // ── Block 4 ──
    document.querySelectorAll('.image-row img').forEach(img => {
        const check = () => { if (img.naturalHeight > img.naturalWidth) img.classList.add('portrait'); };
        if (img.complete) check(); else img.onload = check;
      });

  });
})();
