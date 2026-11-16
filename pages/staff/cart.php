<?php
$me = require_staff();
$q = current_draft($me);
if (is_post() && $q) {
    csrf_check();
    if (input('action') === 'remove') { q('DELETE FROM quotation_items WHERE id = ? AND quotation_id = ?', [(int)input('item'), $q['id']]); recalc_quote((int)$q['id']); }
    if (input('action') === 'discard') { q('DELETE FROM quotation_items WHERE quotation_id = ?', [$q['id']]); q('DELETE FROM quotations WHERE id = ?', [$q['id']]); unset($_SESSION['draft_id']); flash('Draft discarded.'); redirect('app'); }
    redirect('app/quote');
}
$items = $q ? quote_items((int)$q['id']) : [];
if ($q) $q = row('SELECT * FROM quotations WHERE id = ?', [$q['id']]);
$title = $q ? 'Quotation · ' . count($items) . ' items' : 'Quotation';
$right = '<a class="btn sm" href="' . url('app/scan') . '" style="padding:6px 10px">+ Scan</a>';
$nav = 'quotes';
require ROOT . '/views/staff/top.php';
?>
<div class="ab" style="padding-bottom:130px">
  <?php if (!$items): ?>
    <div class="empty"><div style="width:52px;margin:0 auto 10px;color:var(--gold)"><?= icon('cart') ?></div>No items yet.<br>Scan a product QR and tap “Add to quotation”.
      <div style="margin-top:16px"><a class="btn gold" href="<?= url('app/scan') ?>"><?= icon('scan') ?>Scan product</a> <a class="btn" href="<?= url('app/search') ?>">Search</a></div></div>
  <?php else: ?>
    <p class="xs muted" style="margin-bottom:10px"><?= e($q['number']) ?> · Draft</p>
    <?php foreach ($items as $i): $p = ['offer_price' => $i['offer_price'], 'min_price' => $i['min_price']]; $why = approval_reason_for($me, $p, (float)$i['rate']); ?>
      <div class="qi">
        <div class="row sb top"><div class="grow"><b class="small"><?= e($i['name']) ?></b><div class="xs muted"><?= e(item_qty_label($i)) ?></div><div class="xs muted">@ <?= money($i['rate']) ?> / <?= unit_label($i['unit']) ?></div>
          <?php if ($i['note']): ?><div class="xs gold"><?= e($i['note']) ?></div><?php endif ?>
          <?php if ($why): ?><div class="xs red">⚠ <?= e($why) ?> — needs approval</div><?php endif ?></div>
          <b class="nowrap"><?= money($i['amount']) ?></b></div>
        <div class="row" style="margin-top:8px;gap:6px"><a class="btn sm" href="<?= url('app/p/' . $i['code'] . '/add?item=' . $i['id']) ?>"><?= icon('edit') ?>Edit</a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="item" value="<?= $i['id'] ?>"><button class="btn sm ghost red"><?= icon('trash') ?>Remove</button></form></div>
      </div>
    <?php endforeach ?>
    <div class="tot">
      <div><span>Sub-total</span><b><?= money($q['subtotal']) ?></b></div>
      <div><span>GST</span><b><?= money($q['gst_amount']) ?></b></div>
      <div class="g"><span>Grand total</span><span><?= inr($q['total']) ?></span></div>
    </div>
    <form method="post" class="c" style="margin-top:16px" onsubmit="return confirm('Discard this draft quotation?')"><?= csrf_field() ?><input type="hidden" name="action" value="discard"><button class="btn ghost sm muted">Discard draft</button></form>
  <?php endif ?>
</div>
<?php if ($items): ?><div class="sticky-cta"><a class="btn dark block lg" href="<?= url('app/quote/customer') ?>">Next: customer details <?= icon('right') ?></a></div><?php endif ?>
<?php require ROOT . '/views/staff/bottom.php';
