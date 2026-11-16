<?php
$p = product_by_code($params[0]);
if (!$p) not_found();
$title = 'Thank you'; $noHeader = true;
$wa = 'https://wa.me/' . preg_replace('/\D/', '', setting('company_whatsapp')) . '?text=' . rawurlencode('Hi Anant Ceramic, I just requested a quotation for ' . $p['name'] . ' (' . $p['code'] . ').');
require ROOT . '/views/public/top.php';
?>
<div class="ab c" style="padding-top:60px">
  <div style="width:76px;height:76px;border-radius:50%;background:var(--greenbg);color:var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 18px"><span style="width:36px"><?= icon('check') ?></span></div>
  <h2 style="font-size:22px">Thank you<?= !empty($_SESSION['lead_name']) ? ', ' . e(explode(' ', $_SESSION['lead_name'])[0]) : '' ?>!</h2>
  <p class="muted small" style="margin:6px 0 26px">Anant Ceramic will contact you shortly.</p>
  <a class="btn wa block lg" href="<?= e($wa) ?>"><?= icon('wa') ?>Chat on WhatsApp</a>
  <a class="btn block lg" style="margin-top:10px" href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('company_phone'))) ?>"><?= icon('phone') ?>Call <?= e(setting('company_phone')) ?></a>
  <a class="btn block lg" style="margin-top:10px" href="<?= e(setting('company_website')) ?>"><?= icon('globe') ?>Visit <?= e(preg_replace('#^https?://(www\.)?#', '', setting('company_website'))) ?></a>
  <div class="facts" style="margin-top:16px;text-align:left"><div><span><?= icon('pin') ?>Showroom</span><b><?= e(setting('showroom')) ?></b></div></div>
  <a class="small gold b" style="display:block;margin-top:20px" href="<?= url('q/' . $p['code_key']) ?>">‹ Back to <?= e($p['name']) ?></a>
</div>
<?php require ROOT . '/views/public/bottom.php';
