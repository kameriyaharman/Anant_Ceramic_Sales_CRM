<?php
require_admin();
$from = (string)input('from', date('Y-m-01'));
$to = (string)input('to', date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
$toX = date('Y-m-d', strtotime($to . ' +1 day'));

$staff = rows('SELECT st.name, r.name role,
   (SELECT COUNT(*) FROM scans s WHERE s.staff_id = st.id AND s.created_at >= ? AND s.created_at < ?) scans,
   COUNT(q.id) quotes, COALESCE(SUM(q.total),0) value, SUM(q.status = "won") won, COALESCE(SUM(CASE WHEN q.status = "won" THEN q.total END),0) won_value
   FROM staff st LEFT JOIN roles r ON r.id = st.role_id
   LEFT JOIN quotations q ON q.staff_id = st.id AND q.status <> "draft" AND q.created_at >= ? AND q.created_at < ?
   GROUP BY st.id ORDER BY value DESC', [$from, $toX, $from, $toX]);
$products = rows('SELECT p.code, p.name, SUM(s.type = "customer") cust, SUM(s.type = "staff") staff,
   (SELECT COUNT(DISTINCT i.quotation_id) FROM quotation_items i JOIN quotations q ON q.id = i.quotation_id WHERE i.product_id = p.id AND q.status <> "draft" AND q.created_at >= ? AND q.created_at < ?) quoted,
   (SELECT COALESCE(SUM(i.amount),0) FROM quotation_items i JOIN quotations q ON q.id = i.quotation_id WHERE i.product_id = p.id AND q.status = "won" AND q.created_at >= ? AND q.created_at < ?) sold
   FROM products p LEFT JOIN scans s ON s.product_id = p.id AND s.created_at >= ? AND s.created_at < ?
   WHERE p.active = 1 GROUP BY p.id ORDER BY COUNT(s.id) DESC LIMIT 50', [$from, $toX, $from, $toX, $from, $toX]);
$tot = row('SELECT COUNT(*) n, COALESCE(SUM(total),0) v, SUM(status="won") won, COALESCE(SUM(CASE WHEN status="won" THEN total END),0) wv FROM quotations WHERE status <> "draft" AND created_at >= ? AND created_at < ?', [$from, $toX]);
$scans = row('SELECT SUM(type="staff") s, SUM(type="customer") c FROM scans WHERE created_at >= ? AND created_at < ?', [$from, $toX]);
$leads = (int)val('SELECT COUNT(*) FROM leads WHERE created_at >= ? AND created_at < ?', [$from, $toX]);

if (input('csv') === 'staff' || input('csv') === 'products') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="anant-' . input('csv') . "-report-$from-to-$to.csv\"");
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    $data = input('csv') === 'staff' ? $staff : $products;
    if ($data) { fputcsv($o, array_keys($data[0])); foreach ($data as $r) fputcsv($o, $r); }
    exit;
}
$title = 'Reports'; $active = 'reports';
require ROOT . '/views/admin/top.php';
?>
<form class="row wrap" style="margin-bottom:16px"><label class="small muted">From</label><input type="date" name="from" value="<?= e($from) ?>" style="width:auto"><label class="small muted">To</label><input type="date" name="to" value="<?= e($to) ?>" style="width:auto"><button class="btn dark sm">Apply</button>
  <a class="btn sm" href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">Today</a><a class="btn sm" href="?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= date('Y-m-d') ?>">7 days</a><a class="btn sm" href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">This month</a></form>
<div class="grid g5" style="margin-bottom:18px">
  <div class="card kpi"><div class="l">Staff scans</div><div class="v"><?= (int)$scans['s'] ?></div></div>
  <div class="card kpi"><div class="l">Customer scans</div><div class="v"><?= (int)$scans['c'] ?></div></div>
  <div class="card kpi"><div class="l">Leads</div><div class="v"><?= $leads ?></div></div>
  <div class="card kpi"><div class="l">Quotations</div><div class="v"><?= (int)$tot['n'] ?><span class="s gold"><?= lakh($tot['v']) ?></span></div></div>
  <div class="card kpi"><div class="l">Won</div><div class="v"><?= (int)$tot['won'] ?><span class="s green"><?= lakh($tot['wv']) ?></span></div></div>
</div>
<div class="card" style="margin-bottom:18px"><div class="hd"><h3>Staff performance</h3><a class="btn sm" href="?<?= e(http_build_query(['from' => $from, 'to' => $to, 'csv' => 'staff'])) ?>"><?= icon('download') ?>CSV</a></div>
  <div class="tbl-wrap"><table class="t"><thead><tr><th>Staff</th><th>Role</th><th>Scans</th><th>Quotations</th><th>Value</th><th>Won</th><th>Won value</th><th>Conversion</th></tr></thead><tbody>
  <?php foreach ($staff as $s): ?><tr><td class="b"><?= e($s['name']) ?></td><td class="small"><?= e($s['role']) ?></td><td><?= $s['scans'] ?></td><td><?= $s['quotes'] ?></td><td><?= lakh($s['value']) ?></td><td><?= (int)$s['won'] ?></td><td><?= lakh($s['won_value']) ?></td><td><?= $s['quotes'] ? round($s['won'] / $s['quotes'] * 100) : 0 ?>%</td></tr><?php endforeach ?>
  </tbody></table></div></div>
<div class="card"><div class="hd"><h3>Products — scans, quotations, sales</h3><a class="btn sm" href="?<?= e(http_build_query(['from' => $from, 'to' => $to, 'csv' => 'products'])) ?>"><?= icon('download') ?>CSV</a></div>
  <div class="tbl-wrap"><table class="t"><thead><tr><th>Product</th><th>Customer scans</th><th>Staff scans</th><th>In quotations</th><th>Won value</th></tr></thead><tbody>
  <?php foreach ($products as $p): ?><tr><td><b><?= e($p['name']) ?></b> <span class="xs muted"><?= e($p['code']) ?></span></td><td><?= (int)$p['cust'] ?></td><td><?= (int)$p['staff'] ?></td><td><?= $p['quoted'] ?></td><td><?= inr($p['sold']) ?></td></tr><?php endforeach ?>
  </tbody></table></div></div>
<?php require ROOT . '/views/admin/bottom.php';
