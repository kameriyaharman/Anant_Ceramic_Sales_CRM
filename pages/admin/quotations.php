<?php
require_admin();
if (is_post()) {
    csrf_check();
    $act = input('action');
    if ($act === 'decide') {
        $qid = decide_item((int)input('item'), (string)input('decision'), (float)input('counter_rate'));
        flash($qid ? 'Decision saved — staff will see it in the app.' : 'Item not found.', $qid ? 'ok' : 'err');
        redirect(input('back') === 'view' && $qid ? "admin/quotations/$qid" : 'admin/quotations');
    }
    if ($act === 'settings') {
        set_setting('quote_validity', max(1, (int)input('quote_validity')));
        set_setting('quote_terms', trim((string)input('quote_terms')));
        set_setting('quote_prefix', preg_replace('/[^A-Z0-9]/', '', strtoupper((string)input('quote_prefix'))) ?: 'QT');
        set_setting('prices_incl_gst', input('prices_incl_gst') === '1' ? '1' : '0');
        flash('Quotation settings saved.');
        redirect('admin/quotations');
    }
}
$status = (string)input('status', '');
$qs = trim((string)input('q', ''));
$staffF = (int)input('staff', 0);
$where = ['1=1']; $par = [];
if ($status !== '') { $where[] = 'q.status = ?'; $par[] = $status; }
if ($staffF) { $where[] = 'q.staff_id = ?'; $par[] = $staffF; }
if ($qs !== '') { $where[] = '(q.number LIKE ? OR c.name LIKE ? OR c.mobile LIKE ?)'; array_push($par, "%$qs%", "%$qs%", "%$qs%"); }
$list = rows('SELECT q.*, c.name cname, c.mobile cmobile, st.name sname, (SELECT COUNT(*) FROM quotation_items i WHERE i.quotation_id = q.id) items
    FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id LEFT JOIN staff st ON st.id = q.staff_id WHERE ' . implode(' AND ', $where) . ' ORDER BY q.created_at DESC LIMIT 200', $par);

$m0 = date('Y-m-01');
$month = row('SELECT COUNT(*) n, COALESCE(SUM(total),0) v, SUM(status="won") won FROM quotations WHERE created_at >= ? AND status <> "draft"', [$m0]);
$waiting = (int)val('SELECT COUNT(*) FROM quotations WHERE status = "approval"');
$follow = (int)val('SELECT COUNT(*) FROM quotations WHERE DATE(follow_up_at) <= CURDATE() AND status IN ("sent","ready")');
$counts = []; foreach (rows('SELECT status, COUNT(*) n FROM quotations GROUP BY status') as $r) $counts[$r['status']] = $r['n'];
$pending = rows('SELECT i.*, p.name pname, p.min_price, p.offer_price, p.unit, q.number, q.id qid, q.created_at qat, c.name cname, st.name sname, r.max_discount
    FROM quotation_items i JOIN quotations q ON q.id = i.quotation_id JOIN products p ON p.id = i.product_id LEFT JOIN customers c ON c.id = q.customer_id
    LEFT JOIN staff st ON st.id = q.staff_id LEFT JOIN roles r ON r.id = st.role_id WHERE i.approval = "pending" ORDER BY q.created_at');
$staffList = rows('SELECT id, name FROM staff ORDER BY name');

$title = 'Quotations'; $active = 'quotations';
require ROOT . '/views/admin/top.php';
?>
<div class="grid g4" style="margin-bottom:18px">
  <div class="card kpi"><div class="l">This month</div><div class="v"><?= (int)$month['n'] ?><span class="s gold"><?= lakh($month['v']) ?> value</span></div></div>
  <div class="card kpi"><div class="l">Waiting approval</div><div class="v"><?= $waiting ?><span class="s blue"><?= count($pending) ?> discount &gt; limit</span></div></div>
  <div class="card kpi"><div class="l">Won</div><div class="v"><?= (int)$month['won'] ?><span class="s green"><?= $month['n'] ? round($month['won'] / $month['n'] * 100) : 0 ?>%</span></div></div>
  <div class="card kpi"><div class="l">Follow-ups due today</div><div class="v"><?= $follow ?></div></div>
</div>
<div class="grid" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <div class="card">
    <div class="hd wrap">
      <div class="tabs">
        <?php foreach (['' => 'All', 'draft' => 'Draft', 'approval' => 'Approval', 'ready' => 'Ready', 'sent' => 'Sent', 'won' => 'Won', 'lost' => 'Lost'] as $k => $l): ?>
          <a class="<?= $status === $k ? 'on' : '' ?>" href="<?= url('admin/quotations') . '?' . http_build_query(['status' => $k, 'staff' => $staffF ?: null, 'q' => $qs ?: null]) ?>"><?= $l ?><?php if ($k && !empty($counts[$k])): ?><span class="n"><?= $counts[$k] ?></span><?php endif ?></a>
        <?php endforeach ?>
      </div>
      <form class="row"><input type="hidden" name="status" value="<?= e($status) ?>">
        <select name="staff" onchange="this.form.submit()" style="width:auto;padding:6px 8px"><option value="">All staff</option><?php foreach ($staffList as $s): ?><option value="<?= $s['id'] ?>" <?= $staffF == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach ?></select></form>
    </div>
    <div class="tbl-wrap"><table class="t">
      <thead><tr><th>Quotation</th><th>Customer</th><th>Staff</th><th>Items</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody><?php foreach ($list as $q): ?>
        <tr class="click" onclick="location='<?= url('admin/quotations/' . $q['id']) ?>'">
          <td><div class="b small"><?= e($q['number']) ?></div><div class="xs muted"><?= nice_date($q['created_at'], true) ?></div></td>
          <td class="small"><?= e($q['cname'] ?: '—') ?></td><td class="small"><?= e(explode(' ', (string)$q['sname'])[0]) ?></td>
          <td><?= $q['items'] ?></td><td class="b nowrap"><?= inr($q['total']) ?></td><td><?= status_badge($q['status']) ?></td>
        </tr>
      <?php endforeach ?></tbody>
    </table></div>
    <?php if (!$list): ?><div class="empty">No quotations here.</div><?php endif ?>
  </div>

  <div class="stack">
    <?php foreach ($pending as $i): $disc = $i['offer_price'] > 0 ? round(($i['offer_price'] - $i['rate']) / $i['offer_price'] * 100, 1) : 0; ?>
    <div class="appr">
      <div class="row sb" style="margin-bottom:10px"><b>Approval request · <a class="gold" href="<?= url('admin/quotations/' . $i['qid']) ?>"><?= e($i['number']) ?></a></b><span class="xs muted"><?= nice_date($i['qat'], true) ?></span></div>
      <dl class="kv">
        <dt>Customer</dt><dd><?= e($i['cname']) ?></dd><dt>Staff</dt><dd><?= e($i['sname']) ?></dd>
        <dt>Item</dt><dd><?= e($i['pname']) ?> · <?= num($i['unit'] === 'pc' ? $i['boxes'] : $i['area']) ?> <?= unit_label($i['unit']) ?></dd>
        <dt>Asked rate</dt><dd class="red"><?= money($i['rate']) ?> <span class="muted">(min <?= money($i['min_price']) ?>)</span></dd>
        <dt>Discount</dt><dd><?= $disc ?>% <span class="muted">(limit <?= num($i['max_discount'] ?? 0) ?>%)</span></dd>
        <?php if ($i['approval_reason']): ?><dt>Reason</dt><dd><?= e($i['approval_reason']) ?></dd><?php endif ?>
      </dl>
      <form method="post" class="row wrap" style="margin-top:12px">
        <?= csrf_field() ?><input type="hidden" name="action" value="decide"><input type="hidden" name="item" value="<?= $i['id'] ?>">
        <button class="btn sm" style="background:var(--green);color:#fff;border:0" name="decision" value="approve"><?= icon('check') ?>Approve</button>
        <div class="row" style="gap:4px"><input name="counter_rate" type="number" step="0.01" placeholder="Counter ₹" style="width:96px;padding:6px 8px" value="<?= e(num($i['min_price'])) ?>"><button class="btn sm" name="decision" value="counter">Counter</button></div>
        <button class="btn sm red" name="decision" value="reject">Reject</button>
      </form>
    </div>
    <?php endforeach ?>

    <form class="card pad" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="settings">
      <h3 style="margin-bottom:12px">Quotation settings</h3>
      <div class="fgrid" style="grid-template-columns:1fr 1fr">
        <div class="field"><label class="f">Validity (days)</label><input name="quote_validity" type="number" value="<?= e(setting('quote_validity', '7')) ?>"></div>
        <div class="field"><label class="f">Number format</label><div class="row" style="gap:4px"><input name="quote_prefix" value="<?= e(setting('quote_prefix', 'QT')) ?>" style="width:70px"><span class="small muted nowrap">-YYMM-0001</span></div></div>
      </div>
      <div class="field"><label class="f">Prices are</label><select name="prices_incl_gst"><option value="0">Excluding GST (GST added on quotation)</option><option value="1" <?= setting('prices_incl_gst') === '1' ? 'selected' : '' ?>>Including GST</option></select></div>
      <div class="field"><label class="f">Terms (printed)</label><textarea name="quote_terms"><?= e(setting('quote_terms')) ?></textarea></div>
      <button class="btn dark block">Save settings</button>
    </form>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
