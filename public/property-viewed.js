(() => {
 const key='oniki:property-viewed:v1';
 const read=()=>{try{const value=JSON.parse(localStorage.getItem(key)||'[]');return Array.isArray(value)?value.filter(n=>Number.isSafeInteger(n)&&n>0).slice(-800):[];}catch{return [];}};
 let viewed=new Set(read());
 const refreshCards=()=>document.querySelectorAll('[data-property-card]').forEach(card=>{
  const seen=viewed.has(Number(card.dataset.propertyCard));card.classList.toggle('property-is-viewed',seen);
 });
 const notify=()=>{refreshCards();document.dispatchEvent(new Event('oniki:viewed-change'));};
 window.ONIKIViewed={
  has:id=>viewed.has(Number(id)),
  mark:id=>{id=Number(id);if(!Number.isSafeInteger(id)||id<1||viewed.has(id))return;viewed.add(id);const list=[...viewed].slice(-800);viewed=new Set(list);try{localStorage.setItem(key,JSON.stringify(list));}catch{}notify();}
 };
 document.addEventListener('click',event=>{const link=event.target.closest('a[data-property-id]');if(link)window.ONIKIViewed.mark(link.dataset.propertyId);});
 const detail=document.querySelector('[data-property-detail]');if(detail)window.ONIKIViewed.mark(detail.dataset.propertyDetail);
 window.addEventListener('storage',event=>{if(event.key===key){viewed=new Set(read());notify();}});
 window.addEventListener('pageshow',()=>{viewed=new Set(read());notify();});
 refreshCards();
})();
