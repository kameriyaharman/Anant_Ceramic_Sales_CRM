<?php
$me = require_staff();
$id = (int)$params[0];
$q = quote_full($id);
if (!$q || !can_see_quote($me, $q)) not_found('Quotation not found');
$own = (int)$q['staff_id'] === (int)$me['id'];

if ((input('resume') || input('action') === 'resume') && $own && $q['status'] === 'draft') { $_SESSION['draft_id'] = $id; redirect('app/quote'); }
if (is_post() && $own) {
    csrf_check();
    $a = input('action');
    if ($a === 'send') {
        if (in_array($q['status'], ['ready', 'sent'], true)) update('quotations', ['status' => 'sent', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        redirect(wa_link((string)$q['cmobile'], quote_share_text($q)));
    }
    if ($a === 'accept') {
        $i = row('SELECT * FROM quotation_items WHERE id = ? AND quotation_id = ? AND approval = "countered"', [(int)input('item'), $id]);
        if ($i) {
            $amount = $i['area'] > 0 ? round($i['area'] * $i['counter_rate'], 2) : round($i['boxes'] * $i['counter_rate'], 2);
            update('quotation_items', ['rate' => $i['counter_rate'], 'amount' => $amount, 'approval' => 'approved', 'counter_rate' => null], 'id = ?', [$i['id']]);
            recalc_quote($id); refresh_quote_status($id);
            flash('Counter rate accepted.');
        }
    }
    if (in_array($a, ['won', 'lost'], true) && in_array($q['status'], ['ready', 'sent', 'won', 'lost'], true)) {
        update('quotations', ['status' => $a, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        flash($a === 'won' ? 'Marked as won 🎉' : 'Marked as lost.');
    }
    if ($a === 'follow') { $f = (string)input('follow_up_at'); update('quotations', ['follow_up_at' => $f ? date('Y-m-d H:i:s', strtotime($f)) : null], 'id = ?', [$id]); flash('Follow-up saved.'); }
    redirect('app/quotes/' . $id);
}
$q = quote_full($id);
$items = quote_items($id);
$pending = array_filter($items, fn($i) => in_array($i['approval'], ['pending', 'countered', 'rejected'], true));
$title = in_array($q['status'], ['ready', 'sent', 'won', 'lost'], true) ? 'Quotation ready' : ($q['status'] === 'approval' ? 'Discount approval' : 'Quotation');
$back = url('app/quotes');
require ROOT . '/views/staff/top.php';
?>
<div class="ab" style="padding-bottom:40px">
  <?php if ($q['status'] !== 'approval' && $q['status'] !== 'draft'): ?>
    <div class="c" style="margin:6px 0 16px"><div style="width:56px;height:56px;border-radius:50%;background:var(--greenbg);color:var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><span style="width:28px"><?= icon('check') ?></span></div>
      <b><?= e($q['number']) ?></b> · <?= inr($q['total']) ?><div class="xs muted"><?= status_badge($q['status']) ?></div></div>
  <?php endif ?>

  <div class="qi" style="background:#faf8f4">
    <div class="xs b gold" style="letter-spacing:.1em">QUOTATION · <?= e($q['number']) ?></div>
    <b><?= e($q['cname'] ?: 'Customer') ?></b><span class="small muted"><?= $q['ccity'] ? ' · ' . e($q['ccity']) : '' ?></span>
    <?php foreach ($items as $i): ?>
      <div class="row sb small" style="padding:6px 0;border-bottom:1px solid #eee6d8"><span class="grow"><?= e($i['name']) ?><span class="xs muted"> · <?= e(item_qty_label($i)) ?> @ <?= money($i['rate']) ?></span></span><b class="nowrap"><?= money($i['amount']) ?></b></div>
    <?php endforeach ?>
    <div class="row sb" style="margin-top:8px"><span class="b">Total</span><b style="font-size:16px"><?= inr($q['total']) ?></b></div>
  </div>

  <?php foreach ($pending as $i): ?>
    <div class="appr" style="margin-bottom:10px">
      <b class="small"><?= e($i['name']) ?></b>
      <?php if ($i['approval'] === 'pending'): ?><div class="small" style="margin-top:4px">Rate <?= money($i['rate']) ?> · <?= e($i['approval_reason']) ?></div><div class="xs blue b" style="margin-top:4px">Sent to admin · waiting for approval</div>
      <?php elseif ($i['approval'] === 'countered'): ?><div class="small" style="margin-top:4px">You asked <?= money($i['rate']) ?> · <b class="gold">Admin countered <?= money($i['counter_rate']) ?></b></div>
        <?php if ($own): ?><div class="row" style="margin-top:8px"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="accept"><input type="hidden" name="item" value="<?= $i['id'] ?>"><button class="btn gold sm">Accept counter</button></form>
          <a class="btn sm" href="<?= url('app/p/' . $i['code'] . '/add?item=' . $i['id']) ?>">Edit</a></div><?php endif ?>
      <?php else: ?><div class="small red" style="margin-top:4px">Rate <?= money($i['rate']) ?> was rejected by admin.</div>
        <?php if ($own): ?><a class="btn sm" style="margin-top:8px" href="<?= url('app/p/' . $i['code'] . '/add?item=' . $i['id']) ?>">Edit rate</a><?php endif ?><?php endif ?>
    </div>
  <?php endforeach ?>

  <?php if ($own && $q['status'] === 'draft'): ?>
    <a class="btn dark block lg" href="<?= url('app/quotes/' . $id . '?resume=1') ?>">Continue editing</a>
  <?php elseif ($q['status'] !== 'approval'): ?>
    <?php if ($own): ?><form method="post" target="_blank"><?= csrf_field() ?><input type="hidden" name="action" value="send"><button class="btn wa block lg" onclick="setTimeout(()=>location.reload(),1500)"><?= icon('wa') ?>Send on WhatsApp</button></form><?php endif ?>
    <div class="row" style="margin-top:10px"><a class="btn grow" target="_blank" href="<?= url('quotation/' . $q['share_token']) ?>"><?= icon('doc') ?>PDF</a><a class="btn grow" target="_blank" href="<?= url('quotation/' . $q['share_token'] . '?print=1') ?>"><?= icon('print') ?>Print</a></div>
    <?php if ($own): ?>
    <div class="sect">Status</div>
    <form method="post" class="row"><?= csrf_field() ?><button class="btn grow <?= $q['status'] === 'won' ? 'gold' : '' ?>" name="action" value="won">Won</button><button class="btn grow <?= $q['status'] === 'lost' ? 'red' : '' ?>" name="action" value="lost">Lost</button></form>
    <form method="post" class="row" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="follow"><input type="datetime-local" name="follow_up_at" value="<?= $q['follow_up_at'] ? date('Y-m-d\TH:i', strtotime($q['follow_up_at'])) : '' ?>"><button class="btn sm">Save follow-up</button></form>
    <a class="btn block" style="margin-top:10px" href="tel:+91<?= e($q['cmobile']) ?>"><?= icon('phone') ?>Call customer</a>
    <?php endif ?>
  <?php else: ?>
    <p class="small muted c" style="margin-top:10px">You can share this quotation once admin approves the discount.</p>
  <?php endif ?>
</div>
<?php require ROOT . '/views/staff/bottom.php';
