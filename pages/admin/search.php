<?php
require_admin();
$qs = trim((string)input('q', ''));
$like = "%$qs%";
$prods = $qs === '' ? [] : rows('SELECT p.*, c.grp, c.name cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1 AND (p.name LIKE ? OR p.code LIKE ? OR p.code_key LIKE ?) ORDER BY p.code LIMIT 20', [$like, $like, '%' . code_key($qs) . '%']);
$quotes = $qs === '' ? [] : rows('SELECT q.*, c.name cname FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.number LIKE ? OR c.name LIKE ? OR c.mobile LIKE ? ORDER BY q.created_at DESC LIMIT 20', [$like, $like, $like]);
$custs = $qs === '' ? [] : rows('SELECT * FROM customers WHERE name LIKE ? OR mobile LIKE ? OR city LIKE ? LIMIT 20', [$like, $like, $like]);
if (count($prods) === 1 && !$quotes && !$custs) redirect('admin/products/' . $prods[0]['id']);
if (count($quotes) === 1 && !$prods && !$custs) redirect('admin/quotations/' . $quotes[0]['id']);
$title = 'Search: ' . $qs; $active = '';
require ROOT . '/views/admin/top.php';
?>
<div class="grid g3" style="align-items:start">
  <div class="card"><div class="hd"><h3>Products (<?= count($prods) ?>)</h3></div><div class="bd">
    <?php foreach ($prods as $p): ?><a class="row" style="padding:7px 0" href="<?= url('admin/products/' . $p['id']) ?>"><img class="thumb" src="<?= e(product_thumb($p)) ?>"><div><b class="small"><?= e($p['name']) ?></b><div class="xs muted"><?= e($p['code']) ?></div></div></a><?php endforeach ?></div></div>
  <div class="card"><div class="hd"><h3>Quotations (<?= count($quotes) ?>)</h3></div><div class="bd">
    <?php foreach ($quotes as $x): ?><a class="row sb small" style="padding:7px 0" href="<?= url('admin/quotations/' . $x['id']) ?>"><span><b><?= e($x['number']) ?></b> · <?= e($x['cname']) ?></span><?= status_badge($x['status']) ?></a><?php endforeach ?></div></div>
  <div class="card"><div class="hd"><h3>Customers (<?= count($custs) ?>)</h3></div><div class="bd">
    <?php foreach ($custs as $c): ?><a class="small" style="display:block;padding:7px 0" href="<?= url('admin/customers?id=' . $c['id']) ?>"><b><?= e($c['name'] ?: 'Unknown') ?></b> · <?= e(mask_mobile($c['mobile'])) ?></a><?php endforeach ?></div></div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
