<?php
require_admin();
$pid = (int)input('product', 0);
$prod = $pid ? product_by_id($pid) : null;
$grp = $prod['grp'] ?? (in_array(input('grp'), ['tiles', 'sanitary', 'fittings'], true) ? input('grp') : 'tiles');
$fields = visibility_fields();
$keys = array_merge(...array_map('array_keys', array_values($fields)));

if (is_post()) {
    csrf_check();
    $data = ['customer' => [], 'staff' => []];
    foreach (['customer', 'staff'] as $side) foreach ($keys as $k) $data[$side][$k] = empty($_POST[$side][$k]) ? 0 : 1;
    $action = input('action');
    if ($action === 'template') {
        q('INSERT INTO visibility_templates (grp, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)', [$grp, json_encode($data)]);
        $n = (int)val('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE c.grp = ? AND p.active = 1 AND p.visibility IS NULL', [$grp]);
        flash("Template saved — applied to $n " . $grp . ' using the default template.');
        redirect('admin/visibility?grp=' . $grp);
    }
    if ($action === 'product' && $prod) {
        update('products', ['visibility' => json_encode($data)], 'id = ?', [$pid]);
        flash('Saved for ' . $prod['name'] . ' only.');
        redirect('admin/visibility?product=' . $pid);
    }
    if ($action === 'reset' && $prod) {
        update('products', ['visibility' => null], 'id = ?', [$pid]);
        flash('Product now follows the ' . $grp . ' template again.');
        redirect('admin/visibility?product=' . $pid);
    }
}

if ($prod) $vis = visibility_for($prod);
else {
    $vis = default_visibility();
    $t = val('SELECT data FROM visibility_templates WHERE grp = ?', [$grp]);
    if ($t) $vis = array_replace_recursive($vis, json_decode($t, true));
}
$sample = $prod ?: row('SELECT p.*, c.grp, c.name cat_name FROM products p JOIN categories c ON c.id = p.category_id WHERE c.grp = ? AND p.active = 1 ORDER BY p.code LIMIT 1', [$grp]);
$grpCount = (int)val('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE c.grp = ? AND p.active = 1', [$grp]);
$products = rows('SELECT p.id, p.code, p.name FROM products p JOIN categories c ON c.id = p.category_id WHERE c.grp = ? AND p.active = 1 ORDER BY p.code LIMIT 500', [$grp]);

$title = 'Scan visibility — what each scan shows'; $active = 'visibility';
require ROOT . '/views/admin/top.php';
$sp = $sample;
$stockN = $sp ? stock_total((int)$sp['id']) : 0;
$st0 = $sp ? (product_stock((int)$sp['id'])[0] ?? []) : [];
?>
<form method="post" id="vf">
<?= csrf_field() ?>
<div class="grid g2" style="align-items:start">
  <div class="card">
    <div class="hd">
      <h3><?= $prod ? e($prod['name']) . ' <span class="xs muted">' . e($prod['code']) . '</span>' : 'Default template · all ' . e($grp) ?></h3>
      <div class="tabs">
        <?php foreach (['tiles' => 'Tiles', 'sanitary' => 'Sanitary', 'fittings' => 'Fittings'] as $k => $l): ?><a class="<?= !$prod && $grp === $k ? 'on' : '' ?>" href="<?= url('admin/visibility?grp=' . $k) ?>"><?= $l ?></a><?php endforeach ?>
        <a class="<?= $prod ? 'on' : '' ?>" href="#" onclick="document.getElementById('pp').classList.toggle('hidden');return false">This product only</a>
      </div>
    </div>
    <div class="bd hidden<?= $prod ? '' : '' ?>" id="pp" style="padding-bottom:12px">
      <select onchange="if(this.value)location='<?= url('admin/visibility') ?>?product='+this.value"><option value="">Choose a <?= e($grp) ?> product…</option>
        <?php foreach ($products as $o): ?><option value="<?= $o['id'] ?>" <?= $o['id'] == $pid ? 'selected' : '' ?>><?= e($o['code'] . ' · ' . $o['name']) ?></option><?php endforeach ?></select>
    </div>
    <table class="t vis-table">
      <thead><tr><th>Field</th><th class="c">Customer scan</th><th class="c">Staff scan</th></tr></thead>
      <tbody>
      <?php foreach ($fields as $sec => $list): ?>
        <tr class="grp"><td colspan="3"><?= $sec ?></td></tr>
        <?php foreach ($list as $k => $label): ?>
          <tr><td class="small"><?= e($label) ?></td>
            <td class="c"><?= switch_input("customer[$k]", (bool)$vis['customer'][$k], $k === 'add_quote', 'data-side="c" data-k="' . $k . '"') ?></td>
            <td class="c"><?= switch_input("staff[$k]", (bool)$vis['staff'][$k], in_array($k, ['whatsapp', 'request'], true), 'data-side="s" data-k="' . $k . '"') ?></td></tr>
          <?php if ($k === 'min'): ?><tr><td class="small muted">Purchase cost <span class="xs">(admin only — never shown)</span></td><td class="c"><?= switch_input('x', false, true) ?></td><td class="c"><?= switch_input('x', false, true) ?></td></tr><?php endif ?>
        <?php endforeach ?>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <div class="stack">
    <div class="info-dark">
      <h3>How one QR works for both</h3>
      <p>📱 <b>Customer</b> scans with any phone camera → opens <b><?= e(preg_replace('#^https?://#', '', $sp ? qr_url($sp) : abs_url('q/AC1001'))) ?></b> → sees only the fields switched on for customers. No login.</p>
      <p>🔑 <b>Staff</b> scans inside the Anant Staff app (logged in) → sees the real data switched on for staff, plus “Add to quotation”. Role permissions (Staff page) can hide dealer / minimum price further.</p>
    </div>
    <?php if ($sp): ?>
    <div class="card pad">
      <h3 style="margin-bottom:14px">Live preview</h3>
      <div class="row" style="justify-content:center;gap:22px;align-items:flex-start;flex-wrap:wrap">
        <?php foreach (['c' => 'Customer scan', 's' => 'Staff scan'] as $side => $lab): ?>
        <div><div class="phone" data-prev="<?= $side ?>"><div class="bar"></div>
          <div class="ph-img" data-f="photos" style="background-image:url('<?= e(product_thumb($sp)) ?>')"></div>
          <div class="pb">
            <div class="xs gold b" data-f="specs"><?= e(strtoupper(cat_label($sp))) ?></div>
            <div class="b" data-f="name" style="font-size:13px"><?= e($sp['name']) ?></div>
            <div class="xs muted" data-f="code"><?= e($sp['code']) ?></div>
            <div class="row" style="gap:6px;margin-top:4px"><b data-f="offer" style="font-size:14px"><?= inr($sp['offer_price']) ?></b><s class="xs muted" data-f="mrp"><?= inr($sp['mrp']) ?></s>
              <span class="badge b-green" data-f="stock_label" style="margin-left:auto;font-size:9px"><?= $stockN > 40 ? 'In stock' : 'Limited stock' ?></span></div>
            <div class="mini-row" data-f="dealer|min">
              <div data-f="dealer">Dealer<b><?= inr($sp['dealer_price']) ?></b></div><div data-f="min">Min. sell<b><?= inr($sp['min_price']) ?></b></div>
            </div>
            <div class="mini-row" data-f="perbox"><div><b><?= e(explode(' ', (string)$sp['size'])[0]) ?></b>size</div><div><b><?= e($sp['finish']) ?></b>finish</div><div><b><?= e($sp['thickness'] ?: '—') ?></b>thick</div><div><b><?= e($sp['coverage_per_box'] ? num($sp['coverage_per_box']) : ($sp['pcs_per_box'] ?: '—')) ?></b>per box</div></div>
            <div class="xs" data-f="stock_qty">Stock <b><?= $stockN ?> <?= stock_unit($sp) ?></b></div>
            <div class="xs" data-f="location">Location <b><?= e(($st0['location'] ?? '') . ($st0['rack'] ?? '' ? ' · ' . $st0['rack'] : '')) ?></b></div>
            <div class="xs" data-f="batch">Batch <b><?= e($st0['batch'] ?? '—') ?></b></div>
            <div class="row wrap xs" data-f="features" style="gap:3px;margin-top:5px"><?php foreach (array_slice(product_features($sp), 0, 3) as $f): ?><span class="chip" style="padding:2px 6px;font-size:9px"><?= e($f) ?></span><?php endforeach ?></div>
            <div class="act" data-f="whatsapp"><span style="background:var(--wa);color:#fff">WhatsApp</span><span style="background:#1c1a17;color:#fff">Call</span></div>
            <div class="act" data-f="request"><span style="border:1px solid #1c1a17">Request a quotation</span></div>
            <div class="act" data-f="add_quote"><span style="background:linear-gradient(135deg,#d4ab5e,#b8893a);color:#fff">+ Add to quotation</span></div>
            <div class="xs muted" data-f="similar" style="margin-top:6px">Similar products ›</div>
          </div></div><div class="c small b" style="margin-top:8px"><?= $lab ?></div></div>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>
    <div class="row wrap">
      <?php if ($prod): ?>
        <button class="btn gold lg" name="action" value="product"><?= icon('check') ?>Save for this product only</button>
        <?php if ($prod['visibility']): ?><button class="btn lg" name="action" value="reset">Reset to <?= e($grp) ?> template</button><?php endif ?>
        <a class="btn lg ghost" href="<?= url('admin/products/' . $prod['id']) ?>">Back to product</a>
      <?php else: ?>
        <button class="btn gold lg" name="action" value="template"><?= icon('check') ?>Apply to <?= number_format($grpCount) ?> <?= e($grp) ?></button>
        <a class="btn lg" href="#" onclick="document.getElementById('pp').classList.remove('hidden');return false">Save for one product only…</a>
      <?php endif ?>
    </div>
  </div>
</div>
</form>
<script>
function prev(){
  ['c','s'].forEach(side=>{
    const on={};document.querySelectorAll('input[data-side="'+side+'"]').forEach(i=>on[i.dataset.k]=i.checked&&!i.disabled);
    document.querySelectorAll('[data-prev="'+side+'"] [data-f]').forEach(el=>{el.style.display=el.dataset.f.split('|').some(k=>on[k])?'':'none'});
  });
}
document.querySelectorAll('input[data-side]').forEach(i=>i.addEventListener('change',prev));prev();
</script>
<?php require ROOT . '/views/admin/bottom.php';
