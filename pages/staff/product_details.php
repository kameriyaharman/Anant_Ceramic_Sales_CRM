<?php
$me = require_staff();
$p = product_by_code($params[0]);
if (!$p) not_found('Product not found');
$vis = visibility_for($p)['staff'];
$stock = product_stock((int)$p['id']);
$total = stock_total((int)$p['id']);
$same = $p['look_group'] ? rows('SELECT p.*, (SELECT COALESCE(SUM(qty),0) FROM product_stock s WHERE s.product_id = p.id AND s.incoming = 0) st FROM products p WHERE p.look_group = ? AND p.id <> ? AND p.active = 1 LIMIT 8', [$p['look_group'], $p['id']]) : [];
$rooms = array_values(array_filter(product_images((int)$p['id']), fn($i) => $i['kind'] === 'room'));
$title = $p['name'] . ' · ' . $p['code'];
$back = url('app/p/' . $p['code']);
require ROOT . '/views/staff/top.php';
?>
<div class="ab" style="padding-bottom:110px">
  <?php if (!empty($vis['stock_qty']) || $me['perm']['location']): ?>
  <div class="sect" style="margin-top:0">Stock by location <span class="small b <?= $total ? 'green' : 'red' ?>"><?= number_format($total) ?> <?= stock_unit($p) ?></span></div>
  <div class="facts">
    <?php foreach ($stock as $s): ?>
      <div><span><?= icon($s['incoming'] ? 'download' : 'pin') ?><?= e($s['location']) ?><?= $me['perm']['location'] && $s['rack'] ? ' · ' . e($s['rack']) : '' ?><?= !empty($vis['batch']) && $s['batch'] ? ' · ' . e($s['batch']) : '' ?></span>
        <b><?= number_format((int)$s['qty']) ?><?= $s['incoming'] && $s['eta'] ? ' · ' . date('j M', strtotime($s['eta'])) : ' ' . ($p['unit'] === 'pc' ? 'pc' : 'boxes') ?></b></div>
    <?php endforeach ?>
    <?php if (!$stock): ?><div><span>No stock entered</span></div><?php endif ?>
  </div>
  <?php endif ?>

  <div class="sect">Specifications</div>
  <div class="facts">
    <?php foreach (['Category' => cat_label($p), 'Size' => $p['size'], 'Thickness' => $p['thickness'], 'Finish' => $p['finish'],
        'Per box' => $p['unit'] !== 'pc' && $p['coverage_per_box'] ? ($p['pcs_per_box'] ? $p['pcs_per_box'] . ' pcs · ' : '') . num($p['coverage_per_box']) . ' sq.ft' : null,
        'Weight / box' => $p['weight_per_box'], 'Where to use' => $p['where_to_use'], 'GST' => num($p['gst']) . '%'] as $k => $v): if (!$v) continue; ?>
      <div><span><?= $k ?></span><b><?= e($v) ?></b></div>
    <?php endforeach ?>
  </div>
  <?php if ($p['description']): ?><p class="small" style="margin-top:10px"><?= nl2br(e($p['description'])) ?></p><?php endif ?>

  <?php if ($rooms || $p['video_url']): ?><div class="sect">See it in a room</div>
    <div class="row" style="overflow-x:auto;gap:8px"><?php foreach ($rooms as $r): ?><img src="<?= e(upload_url($r['path'])) ?>" style="width:220px;height:140px;object-fit:cover;border-radius:10px;flex:none"><?php endforeach ?></div>
    <?php if ($p['video_url']): ?><a class="btn sm" style="margin-top:8px" target="_blank" href="<?= e($p['video_url']) ?>"><?= icon('play') ?>Laying video</a><?php endif ?>
  <?php endif ?>

  <?php if ($same): ?><div class="sect">Same look, other sizes</div>
    <?php foreach ($same as $s): ?><a class="ptile" href="<?= url('app/p/' . $s['code']) ?>"><img src="<?= e(product_thumb($s)) ?>"><div class="grow"><b class="small"><?= e($s['name']) ?></b><div class="xs muted"><?= e($s['code']) ?> · <?= number_format((int)$s['st']) ?> <?= stock_unit($s) ?></div></div><span class="chev"><?= icon('right') ?></span></a><?php endforeach ?>
  <?php endif ?>
</div>
<?php if ($me['perm']['quote'] && !empty($vis['add_quote'])): ?><div class="sticky-cta"><a class="btn gold block lg" href="<?= url('app/p/' . $p['code'] . '/add') ?>"><?= icon('plus') ?>Add to quotation</a></div><?php endif ?>
<?php require ROOT . '/views/staff/bottom.php';
