const ready = (callback) => document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', callback) : callback();
ready(() => {
  const menuToggle = document.getElementById('menu-toggle');
  const menu = document.querySelector('.menu-parent-unordered-list');
  menuToggle?.addEventListener('click', () => menu?.classList.toggle('show-menu'));
  document.querySelectorAll('[data-menu-toggle]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      const panel = trigger.closest('.menu-parent-list')?.querySelector('.mega-menu-dropdown');
      document.querySelectorAll('.mega-menu-dropdown.show').forEach((other) => other !== panel && other.classList.remove('show'));
      panel?.classList.toggle('show');
    });
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.menu-widget')) document.querySelectorAll('.mega-menu-dropdown.show').forEach((x) => x.classList.remove('show'));
  });

  const tickers = [...document.querySelectorAll('.ticker-item')]; let ticker = 0;
  if (tickers.length) { tickers[0].classList.add('ticker-active'); setInterval(() => { tickers[ticker].classList.remove('ticker-active'); ticker = (ticker + 1) % tickers.length; tickers[ticker].classList.add('ticker-active'); }, 4000); }

  const slides = [...document.querySelectorAll('.photo-slide')]; const thumbs = [...document.querySelectorAll('[data-photo-index]')]; let current = 0;
  const showSlide = (index) => { if (!slides.length) return; current = (index + slides.length) % slides.length; slides.forEach((x,i)=>x.classList.toggle('active',i===current)); thumbs.forEach((x,i)=>x.classList.toggle('active',i===current)); };
  document.querySelector('[data-photo-prev]')?.addEventListener('click', () => showSlide(current - 1));
  document.querySelector('[data-photo-next]')?.addEventListener('click', () => showSlide(current + 1));
  thumbs.forEach((thumb) => thumb.addEventListener('click', () => showSlide(Number(thumb.dataset.photoIndex))));
  if (slides.length > 1) setInterval(() => showSlide(current + 1), 5000);

  document.querySelector('[data-expand-services]')?.addEventListener('click', (event) => {
    const root = event.currentTarget.closest('.service-box-expandable-stack-widget'); root?.classList.toggle('services-expanded');
    event.currentTarget.querySelector('.expand-label').textContent = root?.classList.contains('services-expanded') ? 'সংক্ষিপ্ত' : 'সকল সেবাসমূহ দেখুন';
  });
  document.querySelectorAll('[data-file-tab]').forEach((tab) => tab.addEventListener('click', () => {
    const index = tab.dataset.fileTab; document.querySelectorAll('[data-file-tab]').forEach(x=>x.classList.toggle('active',x===tab));
    document.querySelectorAll('[data-file-preview]').forEach(x=>x.hidden=x.dataset.filePreview!==index);
  }));
  document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
  const goTop = document.querySelector('[data-go-top]'); window.addEventListener('scroll',()=>goTop?.classList.toggle('visible',scrollY>300)); goTop?.addEventListener('click',()=>scrollTo({top:0,behavior:'smooth'}));

  const root = document.documentElement; const panel = document.querySelector('.accessibility-card');
  document.querySelector('[data-accessibility-open]')?.addEventListener('click',()=>panel?.classList.toggle('accessibility-card-open'));
  document.querySelector('[data-accessibility-close]')?.addEventListener('click',()=>panel?.classList.remove('accessibility-card-open'));
  document.querySelectorAll('[data-accessibility]').forEach((button)=>button.addEventListener('click',()=>root.classList.toggle(button.dataset.accessibility)));
  let scale=1; document.querySelector('[data-font-plus]')?.addEventListener('click',()=>{scale=Math.min(1.3,scale+.1);root.style.fontSize=`${scale*100}%`}); document.querySelector('[data-font-minus]')?.addEventListener('click',()=>{scale=Math.max(.8,scale-.1);root.style.fontSize=`${scale*100}%`});
  document.querySelectorAll('[data-table-search]').forEach((input)=>input.addEventListener('input',()=>{
    const root=input.closest('.datatable-widget,.content-browse-widget'); const query=input.value.trim().toLocaleLowerCase();
    root?.querySelectorAll('tbody tr,.employee-content-widget').forEach((row)=>row.hidden=!row.textContent.toLocaleLowerCase().includes(query));
  }));
  document.querySelectorAll('[data-confirm]').forEach((form)=>form.addEventListener('submit',(event)=>{if(!confirm(form.dataset.confirm))event.preventDefault()}));
});
