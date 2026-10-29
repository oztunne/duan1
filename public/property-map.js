(() => {
 const cities={osaka:[34.6937,135.5023],tokyo:[35.6762,139.6503],kyoto:[35.0116,135.7681],kobe:[34.6901,135.1956],fukuoka:[33.5902,130.4017]};
 const node=document.getElementById('property-map-data');
 const picker=document.getElementById('property-location-picker');
 if(!node && !picker) return;
 const feedback=document.getElementById('map-feedback');
 const config=node?JSON.parse(node.textContent):{tileUrl:picker.dataset.tiles};
 if(!window.L) { if(feedback) feedback.textContent=config.labels.error; return; }
 function tiles(map) {
  const layer=L.tileLayer(config.tileUrl,{maxZoom:19,minZoom:5,attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'});
  layer.on('tileerror',()=>{if(feedback) feedback.textContent=config.labels.tileerror;});
  return layer.addTo(map);
 }
 if(picker) {
  const lat=document.getElementById('latitude'),lng=document.getElementById('longitude');
  const map=L.map(picker,{scrollWheelZoom:false,maxBounds:[[20,122],[46,154]],maxBoundsViscosity:.8}).setView(cities.osaka,12); tiles(map);
  let dot;
  const setPoint=(a,b)=>{ if(dot) dot.setLatLng([a,b]); else dot=L.circleMarker([a,b],{radius:10,color:'#fff',weight:3,fillColor:'#175eb1',fillOpacity:1}).addTo(map); };
  const fromInputs=()=>{if(lat.value!=='' && lng.value!=='' && Number.isFinite(Number(lat.value)) && Number.isFinite(Number(lng.value))) {setPoint(Number(lat.value),Number(lng.value));map.setView([Number(lat.value),Number(lng.value)],15);} };
  map.on('click',e=>{lat.value=e.latlng.lat.toFixed(7);lng.value=e.latlng.lng.toFixed(7);setPoint(e.latlng.lat,e.latlng.lng);});
  lat.addEventListener('change',fromInputs);lng.addEventListener('change',fromInputs);fromInputs();
  const kind=document.getElementById('kind');
  const update=()=>{const section=document.querySelector('[data-property-fields]');if(section) section.hidden=kind.value!=='property';if(kind.value==='property') requestAnimationFrame(()=>map.invalidateSize());};
  kind?.addEventListener('change',update);if(kind) update();
  return;
 }
 const map=L.map('property-map',{scrollWheelZoom:true,maxBounds:[[20,122],[46,154]],maxBoundsViscosity:.8}).setView(config.center||cities[config.city]||cities.osaka,config.zoom||12); tiles(map);
 document.addEventListener('oniki:map-resize',()=>requestAnimationFrame(()=>map.invalidateSize()));
 const clusters=L.markerClusterGroup?L.markerClusterGroup({maxClusterRadius:55,showCoverageOnHover:false,iconCreateFunction:cluster=>L.divIcon({className:'oniki-cluster',html:'<span>'+cluster.getChildCount()+'</span>',iconSize:[42,42]})}):L.featureGroup();
 clusters.addTo(map);
 const grouped=new Map(),byId=new Map();
 for(const p of config.points) {
  const key=Number(p.lat).toFixed(5)+','+Number(p.lng).toFixed(5);
  if(!grouped.has(key)) grouped.set(key,[]);grouped.get(key).push(p);
 }
 const groups=[];
 for(const points of grouped.values()) {
  const p=points[0],badge=document.createElement('span');badge.className='map-price-label';badge.textContent=points.length>1?points.length+' ⌂':p.price;
  const marker=L.marker([p.lat,p.lng],{title:p.title,icon:L.divIcon({className:'oniki-price-marker',html:badge,iconSize:null,iconAnchor:[35,18]})}).addTo(clusters);
  const popup=document.createElement('div');popup.className='property-map-popup';
  for(const item of points) {
   const block=document.createElement('article');
   if(item.image){const image=document.createElement('img');image.src=item.image;image.alt=item.title;image.loading='lazy';block.append(image);}
   const heading=document.createElement('strong');heading.textContent=item.title;
   const place=document.createElement('p');place.textContent=item.location;
   const price=document.createElement('b');price.textContent=item.price;
   const link=document.createElement('a');link.href=item.url;link.textContent=config.labels.details+' ↗';
   block.append(heading,place,price,link);popup.append(block);byId.set(String(item.id),marker);
  }
  marker.bindPopup(popup,{maxWidth:290});
  marker.on('click',()=>{for(const item of points){const card=document.querySelector('[data-listing-id="'+item.id+'"]');card?.classList.add('map-result-active');} });
  marker.on('popupclose',()=>document.querySelectorAll('.map-result-active').forEach(e=>e.classList.remove('map-result-active')));
  groups.push(marker);
 }
 const b=config.bounds;
 if(b.north && b.south && b.east && b.west) map.fitBounds([[Number(b.south),Number(b.west)],[Number(b.north),Number(b.east)]]);
 else if(groups.length) map.fitBounds(L.featureGroup(groups).getBounds().pad(.22),{maxZoom:15});
 const mapBox=document.getElementById('property-map');
 document.querySelectorAll('[data-map-focus]').forEach(button=>{
  const marker=byId.get(button.dataset.mapFocus);
  button.disabled=!marker;
  button.addEventListener('click',()=>{if(!marker)return;const open=()=>{map.setView(marker.getLatLng(),Math.max(map.getZoom(),15));marker.openPopup();};if(clusters.zoomToShowLayer)clusters.zoomToShowLayer(marker,open);else open();if(document.body.classList.contains('map-app') && innerWidth<900){document.body.classList.add('panel-collapsed');document.dispatchEvent(new Event('oniki:map-resize'));}if(innerWidth<900 && !document.body.classList.contains('map-app'))mapBox.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion:reduce)').matches?'auto':'smooth',block:'center'});});
 });
 document.getElementById('search-map-area').addEventListener('click',()=>{
  const bounds=map.getBounds(),url=new URL(location.href);
  const clamp=(n,min,max)=>Math.max(min,Math.min(max,n)).toFixed(7);
  url.searchParams.set('north',clamp(bounds.getNorth(),20,46));url.searchParams.set('south',clamp(bounds.getSouth(),20,46));
  url.searchParams.set('east',clamp(bounds.getEast(),122,154));url.searchParams.set('west',clamp(bounds.getWest(),122,154));
  url.searchParams.set('mode','map');url.searchParams.delete('page');location.assign(url);
 });
 document.getElementById('reset-map-area').addEventListener('click',()=>{const url=new URL(location.href);['north','south','east','west','page'].forEach(k=>url.searchParams.delete(k));location.assign(url);});
})();
