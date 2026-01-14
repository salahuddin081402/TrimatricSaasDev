/* Adds .tmx-active to the clicked .tmx-radio-item (fallback if :has is unsupported) */
(function(){
  document.addEventListener('change', (e)=>{
    const radio = e.target.closest('.tmx-radio-item input[type="radio"]');
    if(!radio) return;
    const group = radio.closest('.tmx-radio-group');
    if(!group) return;
    group.querySelectorAll('.tmx-radio-item').forEach(l=>l.classList.remove('tmx-active'));
    const label = radio.closest('.tmx-radio-item');
    if(label) label.classList.add('tmx-active');
  });
})();
