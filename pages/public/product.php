<?php
$p = product_by_code($params[0]);
if (!$p) not_found('This product is not available. Please ask our showroom team.');
// a logged-in staff member scanning with the phone camera gets the staff view
if (staff_user() && !admin()) redirect('app/p/' . $p['code'] . '?src=scan');
log_scan((int)$p['id'], 'customer');
$vis = visibility_for($p)['customer'];
$show = fn($k) => !empty($vis[$k]);
$imgs = array_values(array_filter(product_images((int)$p['id']), fn($i) => $i['kind'] === 'photo'));
$total = stock_total((int)$p['id']);
$low = (int)setting('low_stock_boxes', '40');
$base = 'q/' . $p['code_key'];
$title = $show('name') ? $p['name'] : 'Product ' . $p['code'];
$ogImage = $imgs ? abs_url('uploads/' . $imgs[0]['path']) : '';
$noHeader = true;
require ROOT . '/views/public/top.php';
?>
<?php if ($show('photos')): ?>
<div class="gal" style="aspect-ratio:1/1.05"><div class="tr" onscroll="var c=this.nextElementSibling;if(c)c.textContent=(Math.round(this.scrollLeft/this.clientWidth)+1)+'/<?= count($imgs) ?>'">
  <?php foreach ($imgs ?: [['path' => null]] as $im): ?><img src="<?= e($im['path'] ? upload_url($im['path']) : url('assets/img/placeholder.svg')) ?>" alt="<?= e($p['name']) ?>"><?php endforeach ?>
</div><?php if (count($imgs) > 1): ?><span class="cnt">1/<?= count($imgs) ?></span><?php endif ?></div>
<?php else: ?><header class="pub-hd"><span></span><span style="color:var(--gold-d)"><?= logo() ?></span><span></span></header><?php endif ?>
<div class="ab">
  <?php if ($show('specs')): ?><div class="xs b gold" style="letter-spacing:.12em"><?= e(strtoupper(cat_label($p))) ?></div><?php endif ?>
  <?php if ($show('name')): ?><h1 style="font-size:21px;margin-top:2px"><?= e($p['name']) ?></h1><?php endif ?>
  <?php if ($show('code')): ?><div class="xs muted"><?= e($p['code']) ?></div><?php endif ?>
  <div class="row" style="margin-top:8px">
    <?php if ($show('offer')): ?><span style="font-size:24px;font-weight:700"><?= inr($p['offer_price']) ?></span><span class="small muted">/ <?= unit_label($p['unit']) ?></span><?php endif ?>
    <?php if ($show('mrp') && $p['mrp'] > $p['offer_price']): ?><s class="small muted"><?= inr($p['mrp']) ?></s><?php endif ?>
    <?php if ($show('stock_label') || $show('stock_qty')): ?><span class="badge <?= $total > $low ? 'b-green' : ($total > 0 ? 'b-gold' : 'b-red') ?>" style="margin-left:auto"><?= $show('stock_qty') ? number_format($total) . ' ' . stock_unit($p) : ($total > $low ? 'In stock' : ($total > 0 ? 'Limited stock' : 'On order')) ?></span><?php endif ?>
  </div>
  <?php if ($show('dealer') || $show('min')): ?><div class="pbox"><?php if ($show('dealer')): ?><div class="d"><span>Dealer</span><b><?= inr($p['dealer_price']) ?></b></div><?php endif ?><?php if ($show('min')): ?><div class="m"><span>Best price</span><b><?= inr($p['min_price']) ?></b></div><?php endif ?></div><?php endif ?>

  <?php if ($show('specs') || $show('perbox')): ?>
  <div class="spec4">
    <?php if ($show('specs')): ?><div><b><?= e(explode(' ', (string)$p['size'])[0]) ?></b><span><?= str_contains((string)$p['size'], 'mm') ? 'mm' : 'size' ?></span></div><div><b><?= e($p['finish'] ?: '—') ?></b><span>Finish</span></div><?php endif ?>
    <?php if ($show('specs') && $p['thickness']): ?><div><b><?= e($p['thickness']) ?></b><span>Thick</span></div><?php endif ?>
    <?php if ($show('perbox') && $p['coverage_per_box']): ?><div><b><?= num($p['coverage_per_box']) ?> sq.ft</b><span>per box</span></div><?php endif ?>
  </div><?php endif ?>
  <?php if ($show('location') || $show('batch')): $st = product_stock((int)$p['id'])[0] ?? null; if ($st): ?><div class="facts" style="margin-bottom:12px">
    <?php if ($show('location')): ?><div><span><?= icon('pin') ?>Location</span><b><?= e($st['location']) ?></b></div><?php endif ?><?php if ($show('batch') && $st['batch']): ?><div><span>Batch / shade</span><b><?= e($st['batch']) ?></b></div><?php endif ?></div><?php endif; endif ?>
  <?php if ($show('features') && ($f = product_features($p))): ?><div class="row wrap" style="gap:6px;margin-bottom:14px"><?php foreach ($f as $x): ?><span class="chip"><?= e($x) ?></span><?php endforeach ?></div><?php endif ?>

  <?php if ($show('whatsapp')): ?><div class="row" style="margin-bottom:10px">
    <a class="btn wa grow lg" href="<?= url($base . '/go/whatsapp') ?>"><?= icon('wa') ?>WhatsApp</a>
    <a class="btn dark grow lg" href="<?= url($base . '/go/call') ?>"><?= icon('phone') ?>Call</a></div><?php endif ?>
  <?php if ($show('request')): ?><a class="btn block lg" href="<?= url($base . '/request') ?>" style="border-color:var(--ink)"><?= icon('doc') ?>Request a quotation</a><?php endif ?>

  <a class="li" href="<?= url($base . '/details') ?>" style="margin-top:10px"><div class="grow"><b class="small">Details, room photos &amp; similar designs</b></div><span class="chev"><?= icon('right') ?></span></a>
</div>
<?php require ROOT . '/views/public/bottom.php';
