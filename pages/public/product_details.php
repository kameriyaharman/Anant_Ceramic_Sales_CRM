<?php
$p = product_by_code($params[0]);
if (!$p) not_found('This product is not available.');
$vis = visibility_for($p)['customer'];
$show = fn($k) => !empty($vis[$k]);
$base = 'q/' . $p['code_key'];
$rooms = array_values(array_filter(product_images((int)$p['id']), fn($i) => $i['kind'] === 'room'));
$similar = $show('similar') ? rows('SELECT p.* FROM products p WHERE p.active = 1 AND p.id <> ? AND (p.look_group = ? OR p.category_id = ?) ORDER BY (p.look_group = ?) DESC, p.code LIMIT 6', [$p['id'], $p['look_group'], $p['category_id'], $p['look_group']]) : [];
$title = $p['name']; $back = url($base);
require ROOT . '/views/public/top.php';
?>
<div class="ab">
  <?php if ($show('name')): ?><h2 style="font-size:19px;margin-bottom:12px"><?= e($p['name']) ?></h2><?php endif ?>
  <div class="sect" style="margin-top:0">Details</div>
  <div class="facts">
    <?php $rowsD = [];
    if ($show('specs')) $rowsD += ['Category' => cat_label($p), 'Size' => $p['size'], 'Finish' => $p['finish'], 'Thickness' => $p['thickness']];
    if ($show('perbox') && $p['coverage_per_box']) $rowsD['Per box'] = ($p['pcs_per_box'] ? $p['pcs_per_box'] . ' pcs · ' : '') . num($p['coverage_per_box']) . ' sq.ft';
    if ($show('features')) $rowsD['Use'] = $p['where_to_use'];
    if ($show('code')) $rowsD['Code'] = $p['code'];
    foreach ($rowsD as $k => $v): if (!$v) continue; ?><div><span><?= $k ?></span><b><?= e($v) ?></b></div><?php endforeach ?>
  </div>
  <?php if ($show('features') && $p['description']): ?><p class="small" style="margin-top:10px"><?= nl2br(e($p['description'])) ?></p><?php endif ?>

  <?php if ($show('video') && ($rooms || $p['video_url'])): ?>
    <div class="sect">See it in a room</div>
    <div class="row" style="overflow-x:auto;gap:8px">
      <?php foreach ($rooms as $r): ?><img src="<?= e(upload_url($r['path'])) ?>" style="width:230px;height:150px;object-fit:cover;border-radius:10px;flex:none" alt=""><?php endforeach ?>
    </div>
    <?php if ($p['video_url']): ?><a class="btn block" style="margin-top:8px" target="_blank" rel="noopener" href="<?= e($p['video_url']) ?>"><?= icon('play') ?>Watch laying video</a><?php endif ?>
  <?php endif ?>

  <?php if ($similar): ?><div class="sect">Similar designs</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
      <?php foreach ($similar as $s): ?><a href="<?= url('q/' . $s['code_key']) ?>"><img src="<?= e(product_thumb($s)) ?>" style="aspect-ratio:1;object-fit:cover;border-radius:10px;width:100%" alt=""><div class="xs" style="margin-top:4px"><?= e($s['name']) ?></div></a><?php endforeach ?>
    </div><?php endif ?>

  <div style="margin-top:20px">
    <?php if ($show('request')): ?><a class="btn gold block lg" href="<?= url($base . '/request') ?>">Request a quotation</a><?php endif ?>
    <?php if ($show('whatsapp')): ?><div class="row" style="margin-top:8px"><a class="btn wa grow" href="<?= url($base . '/go/whatsapp') ?>"><?= icon('wa') ?>WhatsApp</a><a class="btn dark grow" href="<?= url($base . '/go/call') ?>"><?= icon('phone') ?>Call</a></div><?php endif ?>
  </div>
</div>
<?php require ROOT . '/views/public/bottom.php';
