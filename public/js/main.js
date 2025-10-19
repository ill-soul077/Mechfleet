// Mechfleet UI interactions (no frameworks)
(function(){
  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  // Sidebar toggle
  const sidebar = $('#sidebar');
  const toggleBtn = $('#sidebarToggle');
  const layout = $('.layout');
  if (toggleBtn && sidebar){
    const apply = (open) => {
      const mobile = window.matchMedia('(max-width: 900px)').matches;
      if (mobile){
        sidebar.classList.toggle('open', open);
        sidebar.classList.remove('collapsed');
        if (layout) layout.classList.remove('sidebar-collapsed');
      } else {
        sidebar.classList.toggle('collapsed', !open);
        sidebar.classList.remove('open');
        if (layout) layout.classList.toggle('sidebar-collapsed', !open);
      }
      toggleBtn.setAttribute('aria-expanded', String(open));
    };
    let open = !window.matchMedia('(max-width: 900px)').matches; // desktop open, mobile closed by default
    apply(open);
    toggleBtn.addEventListener('click', ()=>{ open = !open; apply(open); });
    window.addEventListener('resize', ()=>{ apply(open); });
  }

  // Modal helpers
  window.openModal = function(html){
    let modal = $('.modal');
    if (!modal){
      modal = document.createElement('div');
      modal.className = 'modal';
      modal.setAttribute('role','dialog');
      modal.setAttribute('aria-modal','true');
  modal.innerHTML = '<div class="modal-card" role="document" tabindex="-1"><div class="modal-header"><strong>Dialog</strong><button aria-label="Close" class="modal-close">✕</button></div><div class="modal-body"></div></div>';
      document.body.appendChild(modal);
      modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });
      modal.querySelector('.modal-close').addEventListener('click', closeModal);
      document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeModal(); });
    }
    $('.modal-body', modal).innerHTML = html || '';
    modal.classList.add('open');
    $('.modal-card', modal).focus();
  }
  window.closeModal = function(){ const modal = $('.modal'); if (modal) modal.classList.remove('open'); };

  // Toasts
  window.toast = function(msg, type=''){ let t = $('.toast'); if(!t){ t=document.createElement('div'); t.className='toast'; document.body.appendChild(t);} t.textContent=msg; t.className='toast show '+(type||''); setTimeout(()=>{ t.classList.remove('show');}, 2500); };

  // Button loading states
  $$('button[data-loading]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span><span>Loading…</span>';
      setTimeout(()=>{ btn.disabled=false; btn.innerHTML=original; }, 1200);
    });
  });
})();