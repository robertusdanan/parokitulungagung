/* =======================================================
 *  GALLERY SCRIPT – Versi Final v2 (Spinner Loading)
 *  -------------------------------------------------------
 *  • 9 item per halaman (pagination)
 *  • Pencarian dinamis (title & kategori)
 *  • Spinner saat gambar masih dimuat
 *  • Modal gambar aksesibel
 * ======================================================= */

(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    /* ---------- DOM CACHING ---------- */
    const $ = s => document.querySelector(s);
    const gallery     = $('#gallery');
    const pagination  = $('#pagination');
    const searchInput = $('#search-input');
    const searchBtn   = $('#search-button');
    const modal       = $('#image-modal');
    const modalImg    = $('#modal-image');
    const modalClose  = $('.modal-close');

    if (!gallery || !pagination || !searchInput || !searchBtn || !modal || !modalImg || !modalClose) {
      console.error('Elemen penting tidak ditemukan di DOM.');
      return;
    }

   let albums = [];

const fetchAlbums = async () => {
  try {
    const res = await fetch("https://opensheet.elk.sh/17wqbq_SCdfePCk_3firXNuKt_4r_kzMDmOJbGdN6Bbk/portofolio");
    const data = await res.json();

    // Parsing data menjadi struktur objek seperti sebelumnya
    albums = data.map(item => ({
      ids: (item.ids || '').split(',').map(id => id.trim()),
      title: item.title,
      desc: item.desc,
      categories: (item.categories || '').split(',').map(cat => cat.trim()),
      imgSrc: item.imgSrc,
      imgAlt: item.imgAlt
    }));

    renderAlbums(); // render setelah data berhasil di-fetch
  } catch (err) {
    console.error("Gagal mengambil data:", err);
    gallery.innerHTML = '<p style="padding:24px;text-align:center;color:red">Gagal memuat data.</p>';
  }
};

    /* ---------- STATE ---------- */
    const state = { currentPage:1, itemsPerPage:9, keyword:'' };

    /* ---------- UTIL ---------- */
    const clearEl = el => { while (el.firstChild) el.removeChild(el.firstChild); };
    const filterAlbums = () => {
      const q = state.keyword.trim().toLowerCase();
      return albums.filter(({title,categories}) => !q || title.toLowerCase().includes(q) || categories.some(c=>c.toLowerCase().includes(q)) );
    };

    /* ---------- RENDER ---------- */
    const renderPagination = totalPages => {
      clearEl(pagination);
      if (totalPages<=1) return;
      for (let i=1;i<=totalPages;i++){
        const btn=document.createElement('button');
        btn.textContent=i;
        btn.className='page-btn'+(i===state.currentPage?' active':'');
        btn.onclick=()=>{ state.currentPage=i; renderAlbums(); gallery.scrollIntoView({behavior:'smooth'}); };
        pagination.appendChild(btn);
      }
    };

    const renderAlbums = () => {
      const list = filterAlbums();
      const totalPages = Math.ceil(list.length/state.itemsPerPage)||1;
      if (state.currentPage>totalPages) state.currentPage=1;
      const sliceStart=(state.currentPage-1)*state.itemsPerPage;
      const current=list.slice(sliceStart,sliceStart+state.itemsPerPage);

      clearEl(gallery);

      if (!current.length){
        gallery.innerHTML='<p style="grid-column:1/-1;padding:24px;font-size:1.2rem;color:#555;text-align:center">Tidak ada album yang sesuai dengan pencarian.</p>';
      } else {
        current.forEach(a=>{
          const card=document.createElement('article');
          card.className='card';
          card.tabIndex=0;
          card.setAttribute('aria-label',`${a.title}, ${a.desc}`);

          // wrapper for img + spinner
          const wrapper=document.createElement('div');
          wrapper.className='image-wrapper';

          const spinner=document.createElement('div');
          spinner.className='spinner';
          wrapper.appendChild(spinner);

          const img=document.createElement('img');
          img.alt=a.imgAlt||a.desc;
          img.loading='lazy';
          img.decoding='async';
          img.src=a.imgSrc;
          img.style.opacity='0';

          img.onload=()=>{
            spinner.remove();
            img.style.opacity='1';
          };
          img.onerror=()=>{ spinner.remove(); img.style.opacity='1'; };

          wrapper.appendChild(img);
          card.appendChild(wrapper);

          const content=document.createElement('div');
          content.className='card-content';
          content.innerHTML=`<h3 class="card-title">${a.title}</h3><p class="card-desc">${a.desc}</p>`;
          card.appendChild(content);
          gallery.appendChild(card);
        });
      }
      renderPagination(totalPages);
    };

    /* ---------- EVENTS ---------- */
    const triggerSearch = () => { state.keyword=searchInput.value; state.currentPage=1; renderAlbums(); };
    searchBtn.onclick=triggerSearch;
    searchInput.oninput=triggerSearch;
    searchInput.onkeydown=e=>{ if(e.key==='Enter'){ e.preventDefault(); triggerSearch(); } };

    gallery.onclick=e=>{ const img=e.target.closest('img'); if(img) openModal(img.src,img.alt); };

    /* ---------- MODAL ---------- */
    const openModal=(src,alt='')=>{ modalImg.src=src; modalImg.alt=alt; modal.classList.add('is-open'); modalImg.focus(); };
    const closeModal=()=>{ modal.classList.remove('is-open'); modalImg.src=''; modalImg.alt=''; };
    modalClose.onclick=closeModal;
    modal.onclick=e=>{ if(e.target===modal) closeModal(); };
    document.onkeydown=e=>{ if(e.key==='Escape' && modal.classList.contains('is-open')) closeModal(); };

    /* ---------- INIT ---------- */
    fetchAlbums();
  });
})();
