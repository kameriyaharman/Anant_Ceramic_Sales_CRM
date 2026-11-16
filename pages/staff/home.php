<?php
$me = require_staff();
$today = date('Y-m-d');
$scans = (int)val('SELECT COUNT(*) FROM scans WHERE staff_id = ? AND DATE(created_at) = ?', [$me['id'], $today]);
$qt = row('SELECT COUNT(*) n, COALESCE(SUM(total),0) v FROM quotations WHERE staff_id = ? AND DATE(created_at) = ? AND status <> "draft"', [$me['id'], $today]);
$draft = current_draft($me) ?: row('SELECT * FROM quotations WHERE staff_id = ? AND status = "draft" ORDER BY updated_at DESC LIMIT 1', [$me['id']]);
if ($draft) $_SESSION['draft_id'] = $draft['id'];
$draftInfo = $draft ? row('SELECT COUNT(i.id) n, c.name cname FROM quotations q LEFT JOIN quotation_items i ON i.quotation_id = q.id LEFT JOIN customers c ON c.id = q.customer_id WHERE q.id = ? GROUP BY q.id', [$draft['id']]) : null;
$alerts = rows('SELECT q.id, q.number, q.status, c.name cname, SUM(i.approval = "countered") ctr, SUM(i.approval = "rejected") rej FROM quotations q JOIN quotation_items i ON i.quotation_id = q.id LEFT JOIN customers c ON c.id = q.customer_id
    WHERE q.staff_id = ? AND i.approval IN ("countered","rejected") GROUP BY q.id ORDER BY q.updated_at DESC LIMIT 3', [$me['id']]);
$follow = rows('SELECT q.id, q.number, q.follow_up_at, c.name cname FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.staff_id = ? AND q.status IN ("sent","ready") AND q.follow_up_at IS NOT NULL AND DATE(q.follow_up_at) <= CURDATE() ORDER BY q.follow_up_at LIMIT 3', [$me['id']]);
$recent = rows('SELECT p.*, MAX(s.created_at) last FROM scans s JOIN products p ON p.id = s.product_id WHERE s.staff_id = ? GROUP BY p.id ORDER BY last DESC LIMIT 6', [$me['id']]);

$title = 'Home'; $brand = true; $nav = 'home';
$right = '<a class="ib" href="' . url('app/quotes?tab=approval') . '">' . icon('bell') . '</a>';
require ROOT . '/views/staff/top.php';
?>
<div class="ab">
  <h2 style="font-size:20px">Hi <?= e(explode(' ', $me['name'])[0]) ?> 👋</h2>
  <p class="small muted" style="margin-bottom:14px"><?= e($me['showroom'] ?: $me['role_name']) ?></p>
  <a class="hero" href="<?= url('app/scan') ?>"><?= icon('scan') ?><b>Scan product QR</b><span><?= $me['perm']['quote'] ? 'See price, stock &amp; add to quotation' : 'See product details &amp; stock' ?></span></a>
  <div class="stats"><div><b><?= $scans ?></b><span>Scans today</span></div><div><b><?= (int)$qt['n'] ?></b><span>Quotations</span></div><div><b><?= lakh($qt['v']) ?></b><span>Quoted</span></div></div>

  <?php foreach ($alerts as $a): ?>
    <a class="li" href="<?= url('app/quotes/' . $a['id']) ?>" style="background:#fffaf0;border-radius:12px;padding:12px;margin-bottom:8px;border:1px solid #f0d9a8">
      <span class="ic"><?= icon('bell') ?></span><div class="grow"><b class="small"><?= e($a['number']) ?> · <?= e($a['cname']) ?></b><div class="xs muted"><?= $a['ctr'] ? 'Admin sent a counter rate — tap to accept' : 'Rate rejected by admin — tap to edit' ?></div></div><?= icon('right') ?></a>
  <?php endforeach ?>

  <?php if ($draft && $me['perm']['quote']): ?>
  <div class="sect">Open quotation <span class="small gold"><?= e($draft['number']) ?></span></div>
  <a class="li" href="<?= url('app/quote') ?>"><span class="ic"><?= icon('doc') ?></span><div class="grow"><b class="small"><?= e($draftInfo['cname'] ?: 'New customer') ?> · <?= (int)$draftInfo['n'] ?> items</b><div class="xs muted">Draft · tap to continue</div></div><span class="chev"><?= icon('right') ?></span></a>
  <?php endif ?>

  <?php if ($follow): ?><div class="sect">Follow-ups due</div>
    <?php foreach ($follow as $f): ?><a class="li" href="<?= url('app/quotes/' . $f['id']) ?>"><span class="ic"><?= icon('phone') ?></span><div class="grow"><b class="small"><?= e($f['cname']) ?></b><div class="xs muted"><?= e($f['number']) ?> · <?= nice_date($f['follow_up_at'], true) ?></div></div><span class="chev"><?= icon('right') ?></span></a><?php endforeach ?>
  <?php endif ?>

  <div class="sect">Recent scans</div>
  <?php foreach ($recent as $p): ?>
    <a class="li" href="<?= url('app/p/' . $p['code']) ?>"><span class="ic"><?= icon('layers') ?></span><div class="grow"><b class="small"><?= e($p['name']) ?></b><div class="xs muted"><?= e($p['code']) ?> · <?= inr($p['offer_price']) ?> / <?= unit_label($p['unit']) ?></div></div><span class="chev"><?= icon('right') ?></span></a>
  <?php endforeach ?>
  <?php if (!$recent): ?><p class="small muted">No scans yet — tap “Scan product QR”.</p><?php endif ?>
</div>
<?php require ROOT . '/views/staff/bottom.php';
