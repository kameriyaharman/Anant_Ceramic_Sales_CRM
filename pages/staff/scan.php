<?php
$me = require_staff();
$draft = current_draft($me);
$n = $draft ? (int)val('SELECT COUNT(*) FROM quotation_items WHERE quotation_id = ?', [$draft['id']]) : 0;
$title = 'Scan product QR'; $noHeader = true; $noNav = true;
require ROOT . '/views/staff/top.php';
?>
<div class="scanner">
  <video id="v" playsinline muted></video>
  <div class="frame"><i></i><i></i><i></i><i></i><div class="line"></div></div>
  <div class="sh"><a href="<?= url('app') ?>"><?= icon('x') ?></a><b style="font-size:14px">Scan product QR</b><button id="torch" type="button"><?= icon('flash') ?></button></div>
  <div class="msg" id="msg"></div>
  <div class="sf">Point at the QR sticker on the box, display or sample
    <div class="row">
      <a class="btn" href="<?= url('app/search') ?>" style="background:#fff;border:0"><?= icon('type') ?>Type code</a>
      <?php if ($me['perm']['quote']): ?><a class="btn" href="<?= url('app/quote') ?>" style="background:rgba(255,255,255,.15);color:#fff;border:0">Quote: <?= $n ?> items</a><?php endif ?>
    </div>
  </div>
</div>
<canvas id="c" hidden></canvas>
<script src="<?= asset('js/jsQR.js') ?>"></script>
<script>
const v=document.getElementById('v'),c=document.getElementById('c'),ctx=c.getContext('2d',{willReadFrequently:true}),msg=document.getElementById('msg');
let stream,done=false,detector=null,track;
function go(text){
  if(done)return;text=(text||'').trim();if(!text)return;
  let code=text;const m=text.match(/\/q\/([A-Za-z0-9-]+)/);if(m)code=m[1];
  else if(/^https?:\/\//.test(text)){msg.textContent='This QR is not an Anant product QR';return}
  done=true;if(navigator.vibrate)navigator.vibrate(60);
  location.href=window.BASE+'/app/p/'+encodeURIComponent(code)+'?src=scan';
}
async function start(){
  if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){msg.textContent='Camera needs HTTPS. Use “Type code”.';return}
  try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280},height:{ideal:720}},audio:false})}
  catch(e){msg.textContent='Camera permission denied. Allow camera or use “Type code”.';return}
  v.srcObject=stream;await v.play();track=stream.getVideoTracks()[0];
  if('BarcodeDetector' in window){try{const f=await BarcodeDetector.getSupportedFormats();if(f.includes('qr_code'))detector=new BarcodeDetector({formats:['qr_code']})}catch(e){}}
  tick();
}
async function tick(){
  if(done)return;
  if(v.readyState===v.HAVE_ENOUGH_DATA){
    try{
      if(detector){const r=await detector.detect(v);if(r.length){go(r[0].rawValue);return}}
      else{const w=480,h=Math.round(v.videoHeight/v.videoWidth*w)||360;c.width=w;c.height=h;ctx.drawImage(v,0,0,w,h);
        const img=ctx.getImageData(0,0,w,h);const r=jsQR(img.data,w,h,{inversionAttempts:'dontInvert'});if(r&&r.data){go(r.data);return}}
    }catch(e){}
  }
  requestAnimationFrame(tick);
}
document.getElementById('torch').onclick=async()=>{try{const cap=track.getCapabilities();if(cap.torch){const s=track.getSettings();await track.applyConstraints({advanced:[{torch:!s.torch}]})}}catch(e){}};
window.addEventListener('pagehide',()=>{if(stream)stream.getTracks().forEach(t=>t.stop())});
start();
</script>
<?php require ROOT . '/views/staff/bottom.php';
