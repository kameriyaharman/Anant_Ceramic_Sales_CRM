<?php
require_admin();
$id = (int)$params[0];
$q = quote_full($id);
if (!$q) not_found('Quotation not found');
if (is_post()) {
    csrf_check();
    if (input('action') === 'status') {
        $s = (string)input('status');
        if (in_array($s, ['draft', 'approval', 'ready', 'sent', 'won', 'lost'], true)) update('quotations', ['status' => $s, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        $f = (string)input('follow_up_at');
        update('quotations', ['follow_up_at' => $f ? date('Y-m-d H:i:s', strtotime($f)) : null, 'notes' => trim((string)input('notes')) ?: null], 'id = ?', [$id]);
        flash('Quotation updated.');
    }
    redirect('admin/quotations/' . $id);
}
$items = quote_items($id);
$title = $q['number']; $active = 'quotations';
$actions = '<a class="btn" href="' . url('quotation/' . $q['share_token']) . '" target="_blank">' . icon('print') . 'PDF / Print</a>'
    . '<a class="btn wa" href="' . e(wa_link((string)$q['cmobile'], quote_share_text($q))) . '" target="_blank">' . icon('wa') . 'WhatsApp</a>';
$head = '<link rel="stylesheet" href="' . asset('css/quote.css') . '">';
require ROOT . '/views/admin/top.php';
?>
<div class="grid" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <div><?php require ROOT . '/views/quote_doc.php'; ?></div>
  <div class="stack">
    <div class="card pad">
      <div class="row sb" style="margin-bottom:12px"><h3>Status</h3><?= status_badge($q['status']) ?></div>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="status">
        <div class="field"><label class="f">Change status</label><select name="status"><?php foreach (['draft' => 'Draft', 'approval' => 'Needs approval', 'ready' => 'Ready', 'sent' => 'Sent', 'won' => 'Won', 'lost' => 'Lost'] as $k => $l): ?><option value="<?= $k ?>" <?= $q['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
        <div class="field"><label class="f">Follow-up</label><input type="datetime-local" name="follow_up_at" value="<?= $q['follow_up_at'] ? date('Y-m-d\TH:i', strtotime($q['follow_up_at'])) : '' ?>"></div>
        <div class="field"><label class="f">Internal notes</label><textarea name="notes"><?= e($q['notes']) ?></textarea></div>
        <button class="btn dark block">Update</button>
      </form>
      <dl class="kv" style="margin-top:14px"><dt>Staff</dt><dd><?= e($q['sname'] ?: '—') ?></dd><dt>Created</dt><dd><?= date('j M Y, g:i A', strtotime($q['created_at'])) ?></dd>
        <dt>Customer</dt><dd><?= e($q['cname']) ?> · <a class="gold" href="tel:<?= e($q['cmobile']) ?>"><?= e(mask_mobile($q['cmobile'])) ?></a></dd></dl>
    </div>
    <div class="card pad">
      <h3 style="margin-bottom:10px">Items &amp; approvals</h3>
      <?php foreach ($items as $i): $disc = $i['offer_price'] > 0 ? round(($i['offer_price'] - $i['rate']) / $i['offer_price'] * 100, 1) : 0; ?>
        <div style="padding:10px 0;border-bottom:1px solid #f2eee7">
          <div class="row sb"><b class="small"><?= e($i['name']) ?></b><?= $i['approval'] !== 'none' ? status_badge($i['approval']) : '' ?></div>
          <div class="xs muted"><?= e(item_qty_label($i)) ?> · <?= money($i['rate']) ?> (offer <?= money($i['offer_price']) ?>, min <?= money($i['min_price']) ?>) · <?= $disc ?>% off</div>
          <?php if ($i['approval'] === 'countered'): ?><div class="xs gold">Countered at <?= money($i['counter_rate']) ?> — waiting for staff</div><?php endif ?>
          <?php if ($i['approval'] === 'pending'): ?>
          <form method="post" class="row wrap" style="margin-top:8px"><?= csrf_field() ?><input type="hidden" name="action" value="decide"><input type="hidden" name="back" value="view"><input type="hidden" name="item" value="<?= $i['id'] ?>">
            <button class="btn sm" style="background:var(--green);color:#fff;border:0" name="decision" value="approve" formaction="<?= url('admin/quotations') ?>">Approve</button>
            <input name="counter_rate" type="number" step="0.01" value="<?= e(num($i['min_price'])) ?>" style="width:90px;padding:6px"><button class="btn sm" name="decision" value="counter" formaction="<?= url('admin/quotations') ?>">Counter</button>
            <button class="btn sm red" name="decision" value="reject" formaction="<?= url('admin/quotations') ?>">Reject</button></form>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
