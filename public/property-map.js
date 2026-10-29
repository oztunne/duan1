(() => {
 const cities={osaka:[34.6937,135.5023],tokyo:[35.6762,139.6503],kyoto:[35.0116,135.7681],kobe:[34.6901,135.1956],fukuoka:[33.5902,130.4017]};
 const node=document.getElementById('property-map-data');
 const picker=document.getElementById('property-location-picker');
 if(!node && !picker) return;
 const feedback=document.getElementById('map-feedback');
 const config=node?JSON.parse(node.textContent):{tileUrl:picker.dataset.tiles,styleUrl:picker.dataset.style};
 if(!window.L) { if(feedback) feedback.textContent=config.labels.error; return; }
 function tiles(map) {
  let fallback,vector;
  const raster=()=>{if(fallback)return fallback;
   if(vector && map.hasLayer(vector))map.removeLayer(vector);
   fallback=L.tileLayer(config.tileUrl,{maxZoom:19,minZoom:5,className:'oniki-muted-raster',attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'});
   fallback.on('tileerror',()=>{if(feedback)feedback.textContent=config.labels.tileerror;});fallback.addTo(map);return fallback;
  };
  if(!window.maplibregl || !L.maplibreGL) return raster();
  try {
   vector=L.maplibreGL({style:config.styleUrl,interactive:false,attributionControl:false,attribution:'<a href="https://openfreemap.org/">OpenFreeMap</a> &copy; <a href="https://www.openmaptiles.org/">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);
   const gl=vector.getMaplibreMap();let loaded=false,failed=false;
   // Original road-number badges, drawn locally to fit the label text.
   gl.on('styleimagemissing',event=>{
    if(!['oniki-route-blue','oniki-route-green'].includes(event.id)||gl.hasImage(event.id))return;
    const canvas=document.createElement('canvas');canvas.width=64;canvas.height=44;
    const context=canvas.getContext('2d');
    context.beginPath();context.roundRect(2,2,60,40,event.id==='oniki-route-green'?6:14);
    context.fillStyle=event.id==='oniki-route-green'?'#669a3f':'#3e80c4';context.fill();
    context.lineWidth=3;context.strokeStyle='#ffffff';context.stroke();
    gl.addImage(event.id,context.getImageData(0,0,64,44),{pixelRatio:2});
   });
   gl.on('load',()=>{loaded=true;});
   gl.on('error',()=>{if(!loaded&&!failed){failed=true;raster();}});
   return vector;
  } catch(error) {return raster();}
 }
 if(picker) {
  const lat=document.getElementById('latitude'),lng=document.getElementById('longitude');
  const map=L.map(picker,{scrollWheelZoom:false,maxZoom:19,minZoom:5,maxBounds:[[20,122],[46,154]],maxBoundsViscosity:.8}).setView(cities.osaka,12); tiles(map);
  let dot;
  const address=document.getElementById('property-address'),search=document.getElementById('find-property-address'),status=document.getElementById('property-address-status'),results=document.getElementById('property-address-results');
  let requestVersion=0;
  const setPoint=(a,b)=>{ if(dot) dot.setLatLng([a,b]); else dot=L.circleMarker([a,b],{radius:10,color:'#fff',weight:3,fillColor:'#175eb1',fillOpacity:1}).addTo(map); };
  const fromInputs=()=>{if(lat.value!=='' && lng.value!=='' && Number.isFinite(Number(lat.value)) && Number.isFinite(Number(lng.value))) {setPoint(Number(lat.value),Number(lng.value));map.setView([Number(lat.value),Number(lng.value)],15);} };
  map.on('click',e=>{requestVersion++;results?.replaceChildren();lat.value=e.latlng.lat.toFixed(7);lng.value=e.latlng.lng.toFixed(7);setPoint(e.latlng.lat,e.latlng.lng);if(status)status.textContent='Đã chọn vị trí trên bản đồ. Bạn có thể lưu tin.';});
  lat.addEventListener('change',fromInputs);lng.addEventListener('change',fromInputs);fromInputs();
  address?.addEventListener('input',()=>{
   requestVersion++;lat.value='';lng.value='';if(dot){map.removeLayer(dot);dot=null;}results.replaceChildren();status.textContent='Địa chỉ đã thay đổi. Hãy tìm lại hoặc chọn vị trí mới trên bản đồ.';
  });
  const findAddress=async()=>{
   const query=address.value.trim();if(query.length<3){status.textContent='Nhập địa chỉ nhà trước khi tìm.';address.focus();return;}
   const version=++requestVersion;results.replaceChildren();search.disabled=true;status.textContent='Đang tìm địa chỉ…';
   try {
    const response=await fetch(search.dataset.url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':picker.closest('form').querySelector('[name="_token"]').value},body:JSON.stringify({address:query})});
    const data=await response.json();if(version!==requestVersion)return;
    if(!response.ok)throw new Error(response.status===429?'Bạn tìm quá nhanh. Hãy thử lại sau một phút.':(data.message||'Chưa tìm được địa chỉ. Hãy thử lại.'));
    if(!data.results.length){status.textContent='Không tìm thấy. Hãy viết địa chỉ bằng tiếng Nhật hoặc chọn vị trí trực tiếp trên bản đồ.';return;}
    status.textContent='Chọn địa chỉ phù hợp bên dưới, rồi kiểm tra ghim trên bản đồ.';
    for(const item of data.results){
     const button=document.createElement('button');button.type='button';button.className='secondary small';button.textContent=item.label;
     button.addEventListener('click',()=>{lat.value=item.latitude;lng.value=item.longitude;fromInputs();results.replaceChildren();status.textContent='Đã đặt ghim tại '+item.label+'. Nếu chưa chính xác, nhấp bản đồ để chỉnh lại.';});results.append(button);
    }
   }catch(error){if(version===requestVersion)status.textContent=error instanceof SyntaxError?'Phiên đăng nhập hoặc kết nối đã hết hạn. Tải lại trang rồi thử lại.':error.message;}
   finally{search.disabled=false;}
  };
  search?.addEventListener('click',findAddress);
  address?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();if(!search.disabled)findAddress();}});
  const kind=document.getElementById('kind');
  const update=()=>{const section=document.querySelector('[data-property-fields]');if(section) section.hidden=kind.value!=='property';if(kind.value==='property') requestAnimationFrame(()=>map.invalidateSize());};
  kind?.addEventListener('change',update);if(kind) update();
  return;
 }
 const map=L.map('property-map',{scrollWheelZoom:true,maxZoom:19,minZoom:5,maxBounds:[[20,122],[46,154]],maxBoundsViscosity:.8}).setView(config.center||cities[config.city]||cities.osaka,config.zoom||12); tiles(map);
 document.addEventListener('oniki:map-resize',()=>requestAnimationFrame(()=>map.invalidateSize()));
 const clusters=L.markerClusterGroup?L.markerClusterGroup({maxClusterRadius:55,showCoverageOnHover:false,iconCreateFunction:cluster=>L.divIcon({className:'oniki-cluster'+(cluster.getAllChildMarkers().every(marker=>marker._onikiPoints?.every(p=>window.ONIKIViewed?.has(p.id)))?' cluster-viewed':''),html:'<span>'+cluster.getChildCount()+'</span>',iconSize:[42,42]})}):L.featureGroup();
 clusters.addTo(map);
 const grouped=new Map(),byId=new Map(),markerRecords=[];
 const seen=points=>points.every(p=>window.ONIKIViewed?.has(p.id));
 const markPopup=points=>{if(points.length===1)window.ONIKIViewed?.mark(points[0].id);};
 const refreshMarkers=()=>{
  for(const item of markerRecords){const element=item.marker.getElement();if(element)element.classList.toggle('map-marker-viewed',seen(item.points));}
  clusters.refreshClusters?.();
 };
 document.addEventListener('oniki:viewed-change',refreshMarkers);

 for(const p of config.points) {
  const key=Number(p.lat).toFixed(5)+','+Number(p.lng).toFixed(5);
  if(!grouped.has(key)) grouped.set(key,[]);grouped.get(key).push(p);
 }
 const groups=[];
 for(const points of grouped.values()) {
  const p=points[0],badge=document.createElement('span');badge.className='house-pin';
  badge.innerHTML='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 48" aria-hidden="true"><path d="M18 46C14 35 2 25 2 17a16 16 0 1 1 32 0c0 8-12 18-16 29Z" fill="#ff2f78" stroke="#fff" stroke-width="2"/><circle cx="18" cy="17" r="10.2" fill="#fff"/><path d="m10.5 16 7.5-6.8 7.5 6.8h-2v7h-4v-5h-3v5h-4v-7Z" fill="#ff2f78"/></svg>';
  if(points.length>1){const count=document.createElement('b');count.textContent=points.length;badge.append(count);}
  const marker=L.marker([p.lat,p.lng],{title:p.title,alt:p.title,icon:L.divIcon({className:'oniki-house-marker',html:badge,iconSize:[32,44],iconAnchor:[16,43],popupAnchor:[0,-38]})}).addTo(clusters);
  marker._onikiPoints=points;markerRecords.push({marker,points});
  marker.on('add',refreshMarkers);
  marker.on('popupopen',()=>{markPopup(points);refreshMarkers();});
  const popup=document.createElement('div');popup.className='property-map-popup';
  for(const item of points) {
   const block=document.createElement('article');
   if(item.image){const image=document.createElement('img');image.src=item.image;image.alt=item.title;image.loading='lazy';block.append(image);}
   const heading=document.createElement('strong');heading.textContent=item.title;
   const place=document.createElement('p');place.textContent=item.location;
   const price=document.createElement('b');price.textContent=item.price;
   const link=document.createElement('a');link.href=item.url;link.dataset.propertyId=item.id;link.textContent=config.labels.details+' ↗';
   block.append(heading,place,price,link);popup.append(block);byId.set(String(item.id),marker);
  }
  marker.bindPopup(popup,{maxWidth:290});
  marker.on('click',()=>{for(const item of points){const card=document.querySelector('[data-listing-id="'+item.id+'"]');card?.classList.add('map-result-active');} });
  marker.on('popupclose',()=>document.querySelectorAll('.map-result-active').forEach(e=>e.classList.remove('map-result-active')));
  groups.push(marker);
 }
 refreshMarkers();
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
