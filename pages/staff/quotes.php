<?php
$me = require_staff();
$tab = (string)input('tab', '');
$where = $me['perm']['others'] ? '1=1' : 'q.staff_id = ' . (int)$me['id'];
$par = [];
if ($tab !== '') { $where .= ' AND q.status = ?'; $par[] = $tab; }
$qs = trim((string)input('q', ''));
if ($qs !== '') { $where .= ' AND (q.number LIKE ? OR c.name LIKE ? OR c.mobile LIKE ?)'; array_push($par, "%$qs%", "%$qs%", "%$qs%"); }
$list = rows("SELECT q.*, c.name cname, st.name sname, (SELECT COUNT(*) FROM quotation_items i WHERE i.quotation_id = q.id) items FROM quotations q
   LEFT JOIN customers c ON c.id = q.customer_id LEFT JOIN staff st ON st.id = q.staff_id WHERE $where ORDER BY q.updated_at DESC LIMIT 100", $par);
$title = 'My quotations'; $back = url('app'); $nav = 'quotes';
$right = '<a class="ib" href="' . url('app/quote') . '">' . icon('cart') . '</a>';
require ROOT . '/views/staff/top.php';
?>
<div class="ab">
  <div class="tabs" style="display:flex;overflow-x:auto;flex-wrap:nowrap;margin-bottom:12px">
    <?php foreach (['' => 'All', 'draft' => 'Draft', 'approval' => 'Need approval', 'ready' => 'Ready', 'sent' => 'Sent', 'won' => 'Won', 'lost' => 'Lost'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= url('app/quotes?tab=' . $k) ?>" style="white-space:nowrap"><?= $l ?></a><?php endforeach ?>
  </div>
  <form><input type="hidden" name="tab" value="<?= e($tab) ?>"><input type="search" name="q" value="<?= e($qs) ?>" placeholder="Search customer, mobile, QT number"></form>
  <?php foreach ($list as $q): ?>
    <a class="li" href="<?= url($q['status'] === 'draft' && !$q['customer_id'] && (int)$q['staff_id'] === (int)$me['id'] ? 'app/quotes/' . $q['id'] . '?resume=1' : 'app/quotes/' . $q['id']) ?>">
      <div class="grow"><div class="row sb"><b class="small"><?= e($q['cname'] ?: 'New customer') ?></b><?= status_badge($q['status']) ?></div>
        <div class="row sb xs muted" style="margin-top:3px"><span><?= e(substr($q['number'], -7)) ?> · <?= $q['items'] ?> items<?= $me['perm']['others'] && $q['staff_id'] != $me['id'] ? ' · ' . e($q['sname']) : '' ?></span><b class="small" style="color:var(--ink)"><?= inr($q['total']) ?></b></div>
        <div class="xs muted"><?= $q['follow_up_at'] && in_array($q['status'], ['sent', 'ready'], true) ? 'Follow up ' . date('j M', strtotime($q['follow_up_at'])) : nice_date($q['updated_at'], true) ?></div></div>
    </a>
  <?php endforeach ?>
  <?php if (!$list): ?><div class="empty">No quotations here yet.</div><?php endif ?>
</div>
<?php require ROOT . '/views/staff/bottom.php';
