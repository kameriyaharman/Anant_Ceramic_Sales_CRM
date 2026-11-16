<?php
require_admin();
if (is_post()) {
    csrf_check();
    $ids = array_map('intval', (array)input('ids', []));
    if ($ids && input('action') === 'printed') {
        q('UPDATE products SET qr_status = "printed", reprint_requested = 0 WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
        flash(count($ids) . ' product(s) marked as printed & pasted.');
    }
    redirect('admin/qr');
}
$ids = array_values(array_filter(array_map('intval', (array)($_GET['ids'] ?? []))));
$type = in_array(input('type'), ['box', 'stand', 'tag'], true) ? input('type') : 'box';
$copies = max(1, min(50, (int)input('copies', 1)));
$show = $_GET['show'] ?? ['logo', 'name', 'code', 'finish', 'cta'];
$qs = trim((string)input('q', ''));
$sizes = [
    'box' => ['Box sticker', '50 × 38 mm sticker', 50, 38, 3, 7],
    'stand' => ['Display stand card', '90 × 55 mm card', 90, 55, 2, 5],
    'tag' => ['Sample board tag', '38 × 25 mm tag', 38, 25, 5, 10],
];
[$tName, $tDesc, $wmm, $hmm, $cols, $rowsN] = $sizes[$type];

$selected = $ids ? rows('SELECT p.*, c.grp, c.name cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY p.code', $ids) : [];
$pickWhere = $qs !== '' ? 'AND (p.name LIKE ? OR p.code LIKE ?)' : 'AND (p.qr_status <> "printed" OR p.reprint_requested = 1)';
$pick = rows("SELECT p.id, p.code, p.name, p.qr_status, p.reprint_requested FROM products p WHERE p.active = 1 $pickWhere ORDER BY p.reprint_requested DESC, p.code LIMIT 200", $qs !== '' ? ["%$qs%", "%$qs%"] : []);

$stat = row('SELECT COUNT(*) total, SUM(qr_status = "printed") printed, SUM(qr_status <> "printed") notp, SUM(reprint_requested) reprint FROM products WHERE active = 1');
$labels = [];
foreach ($selected as $p) for ($i = 0; $i < $copies; $i++) $labels[] = $p;
$perSheet = $cols * $rowsN;
$qmm = (int)min($hmm - 4, round($wmm * 0.46));

$title = 'QR codes & labels'; $active = 'qr';
$head = '<style>
.sheet{display:grid;grid-template-columns:repeat(' . $cols . ',' . $wmm . 'mm);grid-auto-rows:' . $hmm . 'mm;gap:2mm;justify-content:center;background:#fff;padding:6mm}
.sheet .lbl{width:' . $wmm . 'mm;height:' . $hmm . 'mm;padding:1.6mm;gap:1.8mm}
.sheet .lbl .qr{width:' . $qmm . 'mm;height:' . $qmm . 'mm}
.sheet .lbl .tx .brand{letter-spacing:.12em;font-size:6.5px}.sheet .lbl .tx .nm{font-size:8.5px}.sheet .lbl .tx .sm,.sheet .lbl .tx .cta{font-size:7px}
.sheet.t-tag .lbl .tx .brand,.sheet.t-tag .lbl .tx .cta{display:none}
.sheet.t-tag .lbl .tx .nm{font-size:7px}.sheet.t-tag .lbl .tx .sm{font-size:6px}
.sheet.t-stand .lbl .tx .nm{font-size:14px}.sheet.t-stand .lbl .tx .sm{font-size:11px}.sheet.t-stand .lbl .tx .brand{font-size:10px}.sheet.t-stand .lbl .tx .cta{font-size:10px}
.sheet.t-stand .lbl .tx .pr{font-size:13px}
.lbl .tx .pr{font-weight:700;font-size:10px;margin-top:2px}
.preview-wrap{background:#e9e5de;border-radius:12px;padding:16px;overflow:auto;max-height:78vh}
@media print{@page{size:A4;margin:6mm}.grid{display:block!important}.preview-wrap{background:none;padding:0;max-height:none;overflow:visible}.sheet{padding:0;page-break-after:always}.lbl{border:0.2mm dashed #bbb!important}}
</style>';
$foot = '<script src="' . asset('js/qrcode.js') . '"></script>';
require ROOT . '/views/admin/top.php';
?>
<div class="grid" style="grid-template-columns:320px 1fr;align-items:start">
<form method="get" class="stack no-print" id="qf">
  <div class="card pad">
    <h3 style="margin-bottom:12px">Print settings</h3>
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <div class="tabs" style="margin-bottom:12px">
      <?php foreach ($sizes as $k => $s): ?><button type="submit" name="type" value="<?= $k ?>" class="<?= $type === $k ? 'on' : '' ?>"><?= $s[0] ?></button><?php endforeach ?>
    </div>
    <div class="kv" style="margin-bottom:12px"><dt>Label size</dt><dd><?= $tDesc ?></dd><dt>Sheet</dt><dd>A4 · <?= $cols ?> × <?= $rowsN ?> = <?= $perSheet ?> labels</dd></div>
    <div class="field"><label class="f">Copies per product</label><input type="number" name="copies" min="1" max="50" value="<?= $copies ?>" onchange="this.form.submit()"></div>
    <label class="f">Show on label</label>
    <?php foreach (['logo' => 'Anant logo', 'name' => 'Product name', 'code' => 'Code & size', 'finish' => 'Finish', 'price' => 'Price on label', 'cta' => '“Scan for details”'] as $k => $l): ?>
      <div class="row sb" style="padding:5px 0"><span class="small"><?= $l ?></span><label class="switch"><input type="checkbox" name="show[]" value="<?= $k ?>" <?= in_array($k, $show, true) ? 'checked' : '' ?> onchange="this.form.submit()"><span></span></label></div>
    <?php endforeach ?>
  </div>
  <div class="card pad">
    <div class="row sb" style="margin-bottom:10px"><h3>Select products</h3><span class="small muted"><?= count($selected) ?> selected</span></div>
    <div class="row" style="margin-bottom:10px"><input name="q" value="<?= e($qs) ?>" placeholder="Search name or code"><button class="btn sm">Go</button></div>
    <div style="max-height:300px;overflow:auto">
      <?php foreach ($selected as $p): ?><label class="row small" style="padding:5px 0"><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" checked onchange="this.form.submit()"><span class="grow"><b><?= e($p['code']) ?></b> · <?= e($p['name']) ?></span></label><?php endforeach ?>
      <?php foreach ($pick as $p): if (in_array((int)$p['id'], $ids, true)) continue; ?>
        <label class="row small" style="padding:5px 0"><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" onchange="this.form.submit()"><span class="grow"><b><?= e($p['code']) ?></b> · <?= e($p['name']) ?></span>
          <?= $p['reprint_requested'] ? '<span class="badge b-red">Reprint</span>' : ($p['qr_status'] !== 'printed' ? '<span class="badge b-gold">New</span>' : '') ?></label>
      <?php endforeach ?>
      <?php if (!$pick && !$selected): ?><p class="small muted">All QR codes are printed. Search to reprint any product.</p><?php endif ?>
    </div>
  </div>
  <div class="card pad">
    <h3 style="margin-bottom:10px">QR status</h3>
    <div class="kv"><dt>QR generated</dt><dd><?= number_format((int)$stat['total']) ?></dd><dt>Printed &amp; pasted</dt><dd class="green"><?= number_format((int)$stat['printed']) ?></dd>
      <dt>Not printed</dt><dd class="gold"><?= number_format((int)$stat['notp']) ?></dd><dt>Reprint requests</dt><dd class="red"><?= (int)$stat['reprint'] ?></dd></div>
  </div>
</form>

<div class="stack">
  <div class="row sb no-print">
    <h3>Print preview · A4 sheet <span class="small muted"><?= count($labels) ?> labels<?= count($labels) ? ' · ' . ceil(count($labels) / $perSheet) . ' sheet(s)' : '' ?></span></h3>
    <div class="row">
      <?php if ($labels): ?>
      <button class="btn" onclick="window.print()"><?= icon('download') ?>PDF</button>
      <button class="btn dark" onclick="window.print()"><?= icon('print') ?>Print</button>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="printed"><?php foreach ($selected as $p): ?><input type="hidden" name="ids[]" value="<?= $p['id'] ?>"><?php endforeach ?><button class="btn gold"><?= icon('check') ?>Mark printed</button></form>
      <?php endif ?>
    </div>
  </div>
  <div class="preview-wrap">
    <?php if (!$labels): ?><div class="empty" style="background:#fff;border-radius:10px">Select products on the left (or tick them on the Products page and click “Print QR for selected”).</div><?php endif ?>
    <?php foreach (array_chunk($labels, $perSheet) as $sheet): ?>
      <div class="sheet t-<?= $type ?>" style="margin-bottom:14px">
        <?php foreach ($sheet as $p): ?>
          <div class="lbl"><div class="qr" data-qr="<?= e(qr_url($p)) ?>"></div><div class="tx">
            <?php if (in_array('logo', $show, true)): ?><div class="brand">ANANT CERAMIC</div><?php endif ?>
            <?php if (in_array('name', $show, true)): ?><div class="nm"><?= e($p['name']) ?></div><?php endif ?>
            <?php if (in_array('code', $show, true)): ?><div class="sm"><?= e($p['code']) ?><?= $p['size'] ? ' · ' . e($p['size']) : '' ?></div><?php endif ?>
            <?php if (in_array('finish', $show, true) && $p['finish']): ?><div class="sm"><?= e($p['finish']) ?></div><?php endif ?>
            <?php if (in_array('price', $show, true)): ?><div class="pr"><?= inr($p['offer_price']) ?> / <?= unit_label($p['unit']) ?></div><?php endif ?>
            <?php if (in_array('cta', $show, true)): ?><div class="cta">Scan for details &amp; price</div><?php endif ?>
          </div></div>
        <?php endforeach ?>
      </div>
    <?php endforeach ?>
  </div>
</div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
