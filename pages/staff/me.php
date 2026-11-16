<?php
$me = require_staff();
$m0 = date('Y-m-01');
$st = row('SELECT COUNT(*) n, COALESCE(SUM(total),0) v, SUM(status = "won") won, COALESCE(SUM(CASE WHEN status = "won" THEN total END),0) wv FROM quotations WHERE staff_id = ? AND created_at >= ? AND status <> "draft"', [$me['id'], $m0]);
$scans = (int)val('SELECT COUNT(*) FROM scans WHERE staff_id = ? AND created_at >= ?', [$me['id'], $m0]);
$title = 'Me'; $nav = 'me'; $back = url('app');
require ROOT . '/views/staff/top.php';
?>
<div class="ab">
  <div class="row" style="margin-bottom:16px"><span class="av" style="width:54px;height:54px;font-size:18px"><?= e(initials($me['name'])) ?></span>
    <div><h2><?= e($me['name']) ?></h2><div class="small muted"><?= e($me['role_name']) ?> · <?= e(mask_mobile($me['mobile'])) ?></div></div></div>
  <div class="sect">This month</div>
  <div class="stats"><div><b><?= $scans ?></b><span>Scans</span></div><div><b><?= (int)$st['n'] ?></b><span>Quotations</span></div><div><b><?= (int)$st['won'] ?></b><span>Won</span></div></div>
  <div class="facts"><div><span>Quoted value</span><b><?= lakh($st['v']) ?></b></div><div><span>Won value</span><b class="green"><?= lakh($st['wv']) ?></b></div>
    <div><span>Discount without approval</span><b>up to <?= num($me['max_discount']) ?>%</b></div></div>
  <div class="sect">My permissions</div>
  <div class="facts"><?php foreach (perm_labels() as $k => $l): ?><div><span><?= e($l) ?></span><b class="<?= $me['perm'][$k] ? 'green' : 'muted' ?>"><?= $me['perm'][$k] ? 'Yes' : 'No' ?></b></div><?php endforeach ?></div>
  <p class="xs muted" style="margin:14px 0">Tip: on Android Chrome, tap ⋮ → “Add to Home screen” to install the Anant Staff app.</p>
  <a class="btn block" href="<?= url('app/logout') ?>"><?= icon('logout') ?>Log out</a>
</div>
<?php require ROOT . '/views/staff/bottom.php';
