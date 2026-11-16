<?php
$me = require_staff();
$p = product_by_code($params[0]);
if (!$p) { flash('No product found for code “' . $params[0] . '”.', 'err'); redirect('app/search?q=' . urlencode($params[0])); }
if (input('src') === 'scan' || input('src') === 'search') log_scan((int)$p['id'], 'staff', (int)$me['id']);
$vis = visibility_for($p)['staff'];
$perm = $me['perm'];
$show = fn($k) => !empty($vis[$k]);
$imgs = array_values(array_filter(product_images((int)$p['id']), fn($i) => $i['kind'] === 'photo'));
$stock = product_stock((int)$p['id']);
$total = stock_total((int)$p['id']);
$main = null; foreach ($stock as $s) if (!$s['incoming'] && (!$main || $s['qty'] > $main['qty'])) $main = $s;
$unitS = unit_label($p['unit']);

$title = 'Scanned · ' . $p['code'];
$back = url('app/scan');
$right = '<a class="ib" href="' . url('app/scan') . '">' . icon('scan') . '</a>';
require ROOT . '/views/staff/top.php';
?>
<?php if ($show('photos')): ?>
<div class="gal"><div class="tr" onscroll="this.nextElementSibling&&(this.nextElementSibling.textContent=(Math.round(this.scrollLeft/this.clientWidth)+1)+'/<?= max(1, count($imgs)) ?>')">
  <?php foreach ($imgs ?: [['path' => null]] as $im): ?><img src="<?= e($im['path'] ? upload_url($im['path']) : url('assets/img/placeholder.svg')) ?>" alt=""><?php endforeach ?>
</div><?php if (count($imgs) > 1): ?><span class="cnt">1/<?= count($imgs) ?></span><?php endif ?></div>
<?php endif ?>
<div class="ab" style="padding-bottom:110px">
  <h2 style="font-size:19px"><?= e($p['name']) ?></h2>
  <?php if ($show('specs')): ?><p class="small muted" style="margin-top:3px"><?= e(implode(' · ', array_filter([cat_label($p), $p['size'], $p['finish'], $p['thickness']]))) ?></p><?php endif ?>
  <?php if ($show('code')): ?><p class="xs gold b" style="margin-top:3px"><?= e($p['code']) ?></p><?php endif ?>

  <div class="pbox">
    <?php if ($show('mrp')): ?><div><span>MRP</span><b><?= inr($p['mrp']) ?></b></div><?php endif ?>
    <?php if ($show('offer')): ?><div><span>Offer</span><b><?= inr($p['offer_price']) ?></b></div><?php endif ?>
    <?php if ($show('dealer') && $perm['dealer']): ?><div class="d"><span>Dealer</span><b><?= inr($p['dealer_price']) ?></b></div><?php endif ?>
    <?php if ($show('min') && $perm['min']): ?><div class="m"><span>Min. sell</span><b><?= inr($p['min_price']) ?></b></div><?php endif ?>
  </div>
  <p class="xs muted" style="margin:-6px 0 12px">Prices per <?= $unitS ?></p>

  <div class="facts">
    <?php if ($show('stock_qty')): ?><div><span><?= icon('box') ?>Stock</span><b class="<?= $total > (int)setting('low_stock_boxes', '40') ? 'green' : 'red' ?>"><?= number_format($total) ?> <?= stock_unit($p) ?></b></div>
    <?php elseif ($show('stock_label')): ?><div><span><?= icon('box') ?>Stock</span><b><?= $total > 0 ? ($total > (int)setting('low_stock_boxes', '40') ? 'In stock' : 'Limited stock') : 'Out of stock' ?></b></div><?php endif ?>
    <?php if ($show('location') && $perm['location'] && $main): ?><div><span><?= icon('pin') ?>Location</span><b><?= e($main['location'] . ($main['rack'] ? ' · Rack ' . $main['rack'] : '')) ?></b></div><?php endif ?>
    <?php if ($show('batch') && $main && $main['batch']): ?><div><span><?= icon('layers') ?>Batch / shade</span><b><?= e($main['batch']) ?></b></div><?php endif ?>
    <?php if ($show('perbox') && $p['unit'] !== 'pc' && $p['coverage_per_box']): ?><div><span><?= icon('stock') ?>Per box</span><b><?= e(($p['pcs_per_box'] ? $p['pcs_per_box'] . ' pcs · ' : '') . num($p['coverage_per_box']) . ' sq.ft') ?></b></div><?php endif ?>
  </div>

  <?php if ($show('features') && ($f = product_features($p))): ?><div class="row wrap" style="gap:6px;margin-top:12px"><?php foreach ($f as $x): ?><span class="chip"><?= e($x) ?></span><?php endforeach ?></div><?php endif ?>

  <a class="li" href="<?= url('app/p/' . $p['code'] . '/details') ?>" style="margin-top:8px"><span class="ic"><?= icon('doc') ?></span><div class="grow"><b class="small">More details</b><div class="xs muted">Stock by location, specifications, other sizes</div></div><span class="chev"><?= icon('right') ?></span></a>
  <form method="post" action="<?= url('app/p/' . $p['code'] . '/reprint') ?>" class="c" style="margin-top:14px"><?= csrf_field() ?>
    <button class="btn ghost sm muted"<?= $p['reprint_requested'] ? ' disabled' : '' ?>><?= icon('qr') ?><?= $p['reprint_requested'] ? 'Reprint requested' : 'QR damaged? Request reprint' ?></button></form>
</div>
<?php if ($perm['quote'] && $show('add_quote')): ?>
<div class="sticky-cta"><a class="btn gold block lg" href="<?= url('app/p/' . $p['code'] . '/add') ?>"><?= icon('plus') ?>Add to quotation</a></div>
<?php endif ?>
<?php require ROOT . '/views/staff/bottom.php';
