(() => {
 const app=document.querySelector('.map-app'),buttons=[...document.querySelectorAll('[data-map-panel]')];
 const refreshMap=()=>document.dispatchEvent(new Event('oniki:map-resize'));
 const close=()=>{app.classList.add('panel-collapsed');buttons.forEach(b=>b.setAttribute('aria-expanded','false'));refreshMap();};
 if(app) {
  for(const button of buttons) button.addEventListener('click',()=>{
   document.querySelectorAll('.map-panel').forEach(p=>p.hidden=p.id!=='panel-'+button.dataset.mapPanel);
   buttons.forEach(b=>b.setAttribute('aria-expanded',String(b===button)));
   app.classList.remove('panel-collapsed');refreshMap();
  });
  document.querySelectorAll('[data-close-panel]').forEach(b=>b.addEventListener('click',close));
  if(matchMedia('(max-width:850px)').matches)close();
  document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
  document.getElementById('map-region')?.addEventListener('change',e=>{
    const prefecture=document.getElementById('map-prefecture');prefecture.value='';e.target.form.requestSubmit();
  });
 }
 document.getElementById('finder-region')?.addEventListener('change',e=>e.target.form.requestSubmit());
})();
