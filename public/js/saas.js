
(function(){
  'use strict';
  const nav=document.getElementById('topnav');
  const toggle=document.getElementById('navToggle');
  const menu=document.getElementById('nav-mobile-menu');
  const onScroll=()=>{if(nav) nav.classList.toggle('scrolled',window.scrollY>8)};
  window.addEventListener('scroll',onScroll,{passive:true}); onScroll();
  if(toggle&&menu){
    toggle.addEventListener('click',()=>{toggle.classList.toggle('open');menu.classList.toggle('open')});
    menu.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{toggle.classList.remove('open');menu.classList.remove('open')}));
  }
  const observer=new IntersectionObserver((entries)=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target)}})
  },{threshold:.08});
  document.querySelectorAll('.nx-reveal').forEach(el=>observer.observe(el));
  document.querySelectorAll('[data-counter]').forEach(el=>{
    const target=parseInt(el.dataset.counter,10)||0;
    let done=false;
    const io=new IntersectionObserver(entries=>{
      if(!entries[0].isIntersecting||done)return; done=true;
      const start=performance.now(), dur=900;
      const tick=t=>{const p=Math.min((t-start)/dur,1);el.textContent=Math.floor(target*(1-Math.pow(1-p,3))).toLocaleString()+(el.dataset.suffix||'');if(p<1)requestAnimationFrame(tick)};
      requestAnimationFrame(tick);io.disconnect();
    });io.observe(el);
  });
})();
