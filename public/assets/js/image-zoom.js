/* Trimatric Image Zoom – works with the zoom modal partial */
(function(){
  const $ = (id)=>document.getElementById(id);
  const modal = $('tmxZoomModal');
  if(!modal){ return; } // partial not included

  const img   = $('tmxZoomImg');
  const range = $('tmxZoomRange');
  const pct   = $('tmxZoomPct');
  const canvas= $('tmxZoomCanvas');
  const closeBtn = $('tmxZoomClose');

  function setZoom(val){
    range.value = val;
    pct.textContent = val + '%';
    const scale = parseInt(val,10)/100;
    img.style.transform = `translate(-50%,-50%) scale(${scale})`;
  }
  function openZoom(src){
    img.src = src;
    setZoom(100);
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden','false');
    img.style.left='50%'; img.style.top='50%';
  }
  function closeZoom(){
    modal.style.display='none';
    modal.setAttribute('aria-hidden','true');
  }

  // public API
  window.tmxOpenZoom = openZoom;
  window.tmxCloseZoom = closeZoom;
  window.tmxSetZoom = setZoom;

  range.addEventListener('input', e=>setZoom(e.target.value));
  closeBtn.addEventListener('click', closeZoom);
  modal.addEventListener('click', (e)=>{ if(e.target===modal) closeZoom(); });

  // drag to pan
  let isDrag=false, imgX=50, imgY=50;
  canvas.addEventListener('mousedown',()=>{isDrag=true; canvas.style.cursor='grabbing';});
  window.addEventListener('mouseup',()=>{isDrag=false; canvas.style.cursor='grab';});
  window.addEventListener('mousemove',(e)=>{
    if(!isDrag) return;
    const rect=canvas.getBoundingClientRect();
    imgX += (e.movementX/rect.width)*100;
    imgY += (e.movementY/rect.height)*100;
    img.style.left = imgX + '%';
    img.style.top  = imgY + '%';
  });

  // convenience: click any .tmx-preview to open zoom
  document.addEventListener('click', (e)=>{
    const prev = e.target.closest('.tmx-preview');
    if(!prev) return;
    const src = prev.dataset.zoomSrc;
    if(!src) return;
    openZoom(src);
  });
})();
