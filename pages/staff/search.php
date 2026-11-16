<?php
$me = require_staff();
$qs = trim((string)input('q', ''));
$grp = (string)input('grp', '');
$where = 'p.active = 1'; $par = [];
if ($qs !== '') { $where .= ' AND (p.name LIKE ? OR p.code LIKE ? OR p.code_key LIKE ? OR p.size LIKE ? OR p.finish LIKE ?)'; array_push($par, "%$qs%", "%$qs%", '%' . code_key($qs) . '%', "%$qs%", "%$qs%"); }
if ($grp !== '') { $where .= ' AND c.grp = ?'; $par[] = $grp; }
$list = rows("SELECT p.*, c.grp, c.name cat_name, (SELECT COALESCE(SUM(qty),0) FROM product_stock s WHERE s.product_id = p.id AND s.incoming = 0) st
   FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $where ORDER BY p.name LIMIT 60", $par);
if ($qs !== '' && count($list) === 1 && strcasecmp(code_key($qs), $list[0]['code_key']) === 0) redirect('app/p/' . $list[0]['code'] . '?src=search');
$title = 'Find product'; $nav = 'products';
require ROOT . '/views/staff/top.php';
?>
<div class="ab">
  <form class="row" style="margin-bottom:10px"><input type="search" name="q" value="<?= e($qs) ?>" placeholder="Name, code or size — e.g. calacatta, AC-1001" autofocus><input type="hidden" name="grp" value="<?= e($grp) ?>"><button class="btn dark"><?= icon('search') ?></button></form>
  <div class="tabs" style="margin-bottom:6px"><?php foreach (['' => 'All', 'tiles' => 'Tiles', 'sanitary' => 'Sanitary', 'fittings' => 'Fittings'] as $k => $l): ?><a class="<?= $grp === $k ? 'on' : '' ?>" href="<?= url('app/search?' . http_build_query(['q' => $qs, 'grp' => $k])) ?>"><?= $l ?></a><?php endforeach ?></div>
  <?php foreach ($list as $p): ?>
    <div class="ptile"><a href="<?= url('app/p/' . $p['code'] . '?src=search') ?>"><img src="<?= e(product_thumb($p)) ?>" alt=""></a>
      <a class="grow" href="<?= url('app/p/' . $p['code'] . '?src=search') ?>"><b class="small"><?= e($p['name']) ?></b>
        <div class="xs muted"><?= e($p['code']) ?> · <?= e($p['size']) ?></div>
        <div class="xs"><b><?= inr($p['offer_price']) ?></b> / <?= unit_label($p['unit']) ?> · <span class="<?= $p['st'] > 0 ? 'green' : 'red' ?>"><?= number_format((int)$p['st']) ?> <?= stock_unit($p) ?></span></div></a>
      <?php if ($me['perm']['quote']): ?><a class="btn sm" href="<?= url('app/p/' . $p['code'] . '/add') ?>">Add</a><?php endif ?>
    </div>
  <?php endforeach ?>
  <?php if (!$list): ?><div class="empty">No products match “<?= e($qs) ?>”.</div><?php endif ?>
  <p class="xs muted c" style="margin-top:16px">Damaged QR? Open the product and tap “Request reprint”.</p>
</div>
<?php require ROOT . '/views/staff/bottom.php';
