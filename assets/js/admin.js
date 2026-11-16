function u(p){return (window.BASE||'')+'/'+p.replace(/^\//,'')}
async function post(path,data){
  const fd=new FormData();fd.append('_csrf',window.CSRF);
  for(const k in data){const v=data[k];if(Array.isArray(v))v.forEach(x=>fd.append(k+'[]',x));else fd.append(k,v)}
  try{const r=await fetch(u(path),{method:'POST',body:fd,headers:{'X-Requested-With':'fetch'}});return await r.json()}catch(e){return {ok:false,error:'Network error'}}
}
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.addEventListener('click',e=>{if(e.target.classList&&e.target.classList.contains('modal'))e.target.classList.remove('open')});

/* QR as inline SVG (qrcode-generator) */
function qrSvg(text,px){
  if(typeof qrcode==='undefined')return '';
  const q=qrcode(0,'M');q.addData(text);q.make();
  const n=q.getModuleCount(),m=2,size=n+m*2;let d='';
  for(let r=0;r<n;r++)for(let c=0;c<n;c++)if(q.isDark(r,c))d+='M'+(c+m)+' '+(r+m)+'h1v1h-1z';
  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '+size+' '+size+'" shape-rendering="crispEdges"'+(px?' width="'+px+'" height="'+px+'"':'')+'><rect width="100%" height="100%" fill="#fff"/><path d="'+d+'" fill="#000"/></svg>';
}
function renderQrs(){document.querySelectorAll('[data-qr]').forEach(el=>{el.innerHTML=qrSvg(el.dataset.qr)})}
function downloadQrPng(text,name){
  const svg=qrSvg(text,1000);const img=new Image();
  img.onload=()=>{const c=document.createElement('canvas');c.width=c.height=1000;c.getContext('2d').drawImage(img,0,0,1000,1000);
    const a=document.createElement('a');a.download=(name||'qr')+'.png';a.href=c.toDataURL('image/png');a.click()};
  img.src='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg);
}
document.addEventListener('DOMContentLoaded',renderQrs);
