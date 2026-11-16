<?php
require_admin();
$today = date('Y-m-d');
$yday = date('Y-m-d', strtotime('-1 day'));
$sc = fn(string $type, string $day) => (int)val('SELECT COUNT(*) FROM scans WHERE type = ? AND DATE(created_at) = ?', [$type, $day]);
$staffToday = $sc('staff', $today); $custToday = $sc('customer', $today);
$custYday = $sc('customer', $yday);
$custChange = $custYday ? round(($custToday - $custYday) / $custYday * 100) : 0;
$activeStaff = (int)val('SELECT COUNT(DISTINCT staff_id) FROM scans WHERE type = "staff" AND DATE(created_at) = ?', [$today]);
$qToday = row('SELECT COUNT(*) n, COALESCE(SUM(total),0) v FROM quotations WHERE DATE(created_at) = ? AND status <> "draft"', [$today]);

$conv = function (string $from, string $to) {
    $r = row('SELECT SUM(status = "won") won, SUM(status IN ("won","lost","sent","ready","approval")) total FROM quotations WHERE created_at >= ? AND created_at < ?', [$from, $to]);
    return $r['total'] ? round($r['won'] / $r['total'] * 100) : 0;
};
$monthStart = date('Y-m-01');
$convNow = $conv($monthStart, date('Y-m-d', strtotime('+1 day')));
$convPrev = $conv(date('Y-m-01', strtotime('first day of last month')), $monthStart);

$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days[] = ['d' => $d, 'lb' => date('D', strtotime($d)), 's' => $sc('staff', $d), 'c' => $sc('customer', $d),
        'q' => (int)val('SELECT COUNT(*) FROM quotations WHERE DATE(created_at) = ? AND status <> "draft"', [$d])];
}
$max = max(1, ...array_map(fn($d) => $d['s'] + $d['c'], $days));

$top = rows('SELECT p.id, p.code, p.name, p.size, COUNT(*) n FROM scans s JOIN products p ON p.id = s.product_id WHERE s.created_at >= ? GROUP BY p.id ORDER BY n DESC LIMIT 5', [date('Y-m-d', strtotime('-6 days'))]);
$latest = rows('SELECT q.*, c.name cname, c.city, st.name sname FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id LEFT JOIN staff st ON st.id = q.staff_id WHERE q.status <> "draft" ORDER BY q.created_at DESC LIMIT 5');
$staffRows = rows('SELECT st.id, st.name,
    (SELECT COUNT(*) FROM scans s WHERE s.staff_id = st.id AND DATE(s.created_at) = ?) scans,
    (SELECT COUNT(*) FROM quotations q WHERE q.staff_id = st.id AND DATE(q.created_at) = ? AND q.status <> "draft") quotes,
    (SELECT COALESCE(SUM(total),0) FROM quotations q WHERE q.staff_id = st.id AND DATE(q.created_at) = ? AND q.status <> "draft") value
    FROM staff st WHERE st.status = "active" ORDER BY scans DESC', [$today, $today, $today]);

$title = 'Dashboard'; $active = 'dashboard';
require ROOT . '/views/admin/top.php';
?>
<div class="grid g5" style="margin-bottom:18px">
  <div class="card kpi"><div class="l">Scans today</div><div class="v"><?= $staffToday + $custToday ?></div></div>
  <div class="card kpi"><div class="l">Staff scans</div><div class="v"><?= $staffToday ?><span class="s gold"><?= $activeStaff ?> staff</span></div></div>
  <div class="card kpi"><div class="l">Customer scans</div><div class="v"><?= $custToday ?><span class="s <?= $custChange >= 0 ? 'green' : 'red' ?>"><?= $custChange >= 0 ? '+' : '' ?><?= $custChange ?>%</span></div></div>
  <div class="card kpi"><div class="l">Quotations today</div><div class="v"><?= $qToday['n'] ?><span class="s green"><?= inr($qToday['v']) ?></span></div></div>
  <div class="card kpi"><div class="l">Converted this month</div><div class="v"><?= $convNow ?>%<span class="s <?= $convNow >= $convPrev ? 'green' : 'red' ?>"><?= $convNow >= $convPrev ? '+' : '' ?><?= $convNow - $convPrev ?>%</span></div></div>
</div>

<div class="grid g-main" style="margin-bottom:18px">
  <div class="card">
    <div class="hd"><h3>Scans &amp; quotations · last 7 days</h3><div class="legend"><span><i style="background:#c99d50"></i>Staff scans</span><span><i style="background:#221e19"></i>Customer scans</span></div></div>
    <div class="bd"><div class="bars">
      <?php foreach ($days as $d): ?>
        <div class="col" title="<?= $d['s'] ?> staff · <?= $d['c'] ?> customer · <?= $d['q'] ?> quotations">
          <div class="xs muted"><?= $d['q'] ?> QT</div>
          <div class="stk"><div class="s1" style="height:<?= $d['s'] / $max * 100 ?>%"></div><div class="s2" style="height:<?= $d['c'] / $max * 100 ?>%"></div></div>
          <div class="lb"><?= $d['lb'] ?></div>
        </div>
      <?php endforeach ?>
    </div></div>
  </div>
  <div class="card">
    <div class="hd"><h3>Most scanned products</h3><a class="small gold" href="<?= url('admin/reports') ?>">Report</a></div>
    <div class="bd">
      <?php foreach ($top as $p): ?>
        <a class="row" href="<?= url('admin/products/' . $p['id']) ?>" style="padding:8px 0;border-bottom:1px solid #f2eee7">
          <img class="thumb" src="<?= e(product_thumb($p)) ?>" alt="">
          <div class="grow"><div class="b small"><?= e($p['name']) ?></div><div class="xs muted"><?= e($p['code']) ?> · <?= e($p['size']) ?></div></div>
          <b><?= $p['n'] ?></b>
        </a>
      <?php endforeach ?>
      <?php if (!$top): ?><div class="empty">No scans yet</div><?php endif ?>
    </div>
  </div>
</div>

<div class="grid g2">
  <div class="card">
    <div class="hd"><h3>Latest quotations</h3><a class="small gold b" href="<?= url('admin/quotations') ?>">View all</a></div>
    <div class="tbl-wrap"><table class="t"><tbody>
      <?php foreach ($latest as $q): ?>
        <tr class="click" onclick="location='<?= url('admin/quotations/' . $q['id']) ?>'">
          <td><div class="b small"><?= e($q['number']) ?></div><div class="xs muted"><?= e($q['cname']) ?><?= $q['city'] ? ' · ' . e($q['city']) : '' ?></div></td>
          <td class="small"><?= e(explode(' ', $q['sname'] ?? '—')[0]) ?> (staff)</td>
          <td class="b small nowrap"><?= inr($q['total']) ?></td>
          <td><?= status_badge($q['status']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody></table></div>
    <?php if (!$latest): ?><div class="empty">No quotations yet</div><?php endif ?>
  </div>
  <div class="card">
    <div class="hd"><h3>Staff today</h3><a class="small gold" href="<?= url('admin/staff') ?>">Manage</a></div>
    <div class="tbl-wrap"><table class="t">
      <thead><tr><th>Staff</th><th>Scans</th><th>Quotes</th><th>Value</th></tr></thead>
      <tbody><?php foreach ($staffRows as $s): ?>
        <tr><td class="b small"><?= e($s['name']) ?></td><td><?= $s['scans'] ?></td><td><?= $s['quotes'] ?></td><td><?= lakh($s['value']) ?></td></tr>
      <?php endforeach ?></tbody>
    </table></div>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
