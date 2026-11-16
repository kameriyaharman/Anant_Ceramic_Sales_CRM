<?php
/** Shareable quotation (link sent on WhatsApp). Print / Save as PDF from the browser. */
$qid = (int)val('SELECT id FROM quotations WHERE share_token = ?', [$params[0]]);
$q = $qid ? quote_full($qid) : null;
if (!$q || $q['status'] === 'draft') not_found('Quotation not found');
$items = quote_items($qid);
$autoPrint = input('print') === '1';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($q['number']) ?> · <?= e(setting('company_name', 'Anant Ceramic')) ?> quotation</title>
<meta name="robots" content="noindex"><link rel="icon" href="<?= asset('img/icon.svg') ?>">
<?= fonts() ?><link rel="stylesheet" href="<?= asset('css/base.css') ?>"><link rel="stylesheet" href="<?= asset('css/quote.css') ?>">
</head><body style="padding:24px 12px">
<div class="no-print row" style="max-width:820px;margin:0 auto 14px;justify-content:flex-end">
  <button class="btn dark" onclick="window.print()"><?= icon('download') ?>Download PDF</button>
  <button class="btn" onclick="window.print()"><?= icon('print') ?>Print</button>
</div>
<?php require ROOT . '/views/quote_doc.php'; ?>
<?php if ($autoPrint): ?><script>window.addEventListener('load',()=>setTimeout(()=>window.print(),400))</script><?php endif ?>
</body></html>
