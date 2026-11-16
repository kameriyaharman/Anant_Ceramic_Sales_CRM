<?php
/** Printable quotation document. Needs $q (quotation row with customer + staff fields) and $items. */
$incl = setting('prices_incl_gst', '0') === '1';
?>
<div class="qdoc">
  <div class="qd-head">
    <div><div class="qd-title">QUOTATION</div><div class="qd-no"><?= e($q['number']) ?> · <?= date('j M Y', strtotime($q['created_at'])) ?></div>
      <?php if ($q['valid_till']): ?><div class="qd-valid">Valid till <?= date('j M Y', strtotime($q['valid_till'])) ?></div><?php endif ?></div>
    <div style="color:#8e6524"><?= logo() ?></div>
  </div>
  <div class="qd-parties">
    <div><div class="qd-lab">TO</div><b><?= e($q['cname'] ?: 'Customer') ?></b>
      <?php if ($q['delivery_address'] ?: $q['caddress']): ?><div><?= e($q['delivery_address'] ?: $q['caddress']) ?></div><?php endif ?>
      <div><?= e(mask_mobile($q['cmobile'])) ?></div>
      <?php if (!empty($q['architect'])): ?><div class="qd-muted">Architect / contractor: <?= e($q['architect']) ?></div><?php endif ?></div>
    <div><div class="qd-lab">FROM</div><b><?= e(setting('company_name', 'Anant Ceramic')) ?></b><div><?= e(setting('company_address')) ?></div>
      <div><?= e(setting('company_phone')) ?><?= $q['sname'] ? ' · Sales: ' . e($q['sname']) : '' ?></div>
      <?php if (setting('company_gstin')): ?><div class="qd-muted">GSTIN <?= e(setting('company_gstin')) ?></div><?php endif ?></div>
  </div>
  <table class="qd-t">
    <thead><tr><th>Product</th><th>Qty</th><th>Rate</th><th style="text-align:right">Amount</th></tr></thead>
    <tbody><?php foreach ($items as $i): ?>
      <tr><td><b><?= e($i['name']) ?></b><div class="qd-muted"><?= e($i['code']) ?><?= $i['size'] ? ' · ' . e($i['size']) : '' ?><?= $i['finish'] ? ' · ' . e($i['finish']) : '' ?></div>
        <?php if ($i['note']): ?><div class="qd-muted"><?= e($i['note']) ?></div><?php endif ?></td>
        <td><?= e(item_qty_label($i)) ?></td><td class="nowrap"><?= money($i['rate']) ?> / <?= unit_label($i['unit']) ?></td><td style="text-align:right" class="nowrap"><?= money($i['amount']) ?></td></tr>
    <?php endforeach ?></tbody>
  </table>
  <div class="qd-tot">
    <div><span>Sub-total<?= $incl ? ' (excl. GST)' : '' ?></span><b><?= money($q['subtotal']) ?></b></div>
    <div><span>GST</span><b><?= money($q['gst_amount']) ?></b></div>
    <div class="gt"><span>Grand total</span><b><?= inr($q['total']) ?></b></div>
  </div>
  <?php if ($q['delivery']): ?><p class="qd-muted" style="margin-top:12px">Delivery: <?= e($q['delivery']) ?></p><?php endif ?>
  <p class="qd-terms"><b>Terms:</b> <?= e(setting('quote_terms')) ?></p>
</div>
