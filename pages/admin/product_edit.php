<?php
require_admin();
$id = isset($params[0]) ? (int)$params[0] : 0;
$p = $id ? product_by_id($id) : null;
if ($id && !$p) not_found('Product not found');
$cats = rows('SELECT * FROM categories ORDER BY FIELD(grp,"tiles","sanitary","fittings"), name');

if (is_post()) {
    csrf_check();
    $action = input('action', 'save');
    if ($action === 'delete' && $p) {
        update('products', ['active' => 0], 'id = ?', [$id]);
        flash('Product removed.'); redirect('admin/products');
    }
    if ($action === 'del_img' && $p) {
        $img = row('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int)input('img'), $id]);
        if ($img) { q('DELETE FROM product_images WHERE id = ?', [$img['id']]); if (!str_starts_with($img['path'], 'seed/')) @unlink(ROOT . '/uploads/' . $img['path']); }
        redirect('admin/products/' . $id . '#photos');
    }
    if ($action === 'qr_printed' && $p) {
        update('products', ['qr_status' => input('v') === 'printed' ? 'printed' : 'generated', 'reprint_requested' => 0], 'id = ?', [$id]);
        redirect('admin/products/' . $id . '#qr');
    }

    $catId = (int)input('category_id');
    $cat = row('SELECT * FROM categories WHERE id = ?', [$catId]);
    $n = fn($k) => ($v = trim((string)input($k))) === '' ? null : (float)str_replace(',', '', $v);
    $s = fn($k) => ($v = trim((string)input($k))) === '' ? null : $v;
    $data = [
        'name' => trim((string)input('name')), 'category_id' => $catId ?: null, 'size' => $s('size'), 'finish' => $s('finish'),
        'thickness' => $s('thickness'), 'pcs_per_box' => $n('pcs_per_box'), 'coverage_per_box' => $n('coverage_per_box'),
        'weight_per_box' => $s('weight_per_box'), 'where_to_use' => $s('where_to_use'), 'description' => $s('description'),
        'look_group' => $s('look_group'), 'unit' => $cat['unit'] ?? 'sqft',
        'features' => json_encode(array_values(array_filter(array_map('trim', (array)input('features', []))))),
        'mrp' => $n('mrp'), 'offer_price' => $n('offer_price'), 'dealer_price' => $n('dealer_price'), 'min_price' => $n('min_price'),
        'purchase_cost' => $n('purchase_cost'), 'gst' => $n('gst') ?? 18, 'video_url' => $s('video_url'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    if ($data['name'] === '') { flash('Product name is required.', 'err'); redirect($id ? "admin/products/$id" : 'admin/products/new'); }
    if ($data['pcs_per_box'] !== null) $data['pcs_per_box'] = (int)$data['pcs_per_box'];

    $code = strtoupper(trim((string)input('code')));
    if (!$p) {
        if ($code === '' || val('SELECT id FROM products WHERE code = ?', [$code])) $code = next_product_code($catId);
        $id = insert('products', $data + ['code' => $code, 'code_key' => code_key($code), 'qr_status' => 'generated']);
    } else {
        if ($code !== '' && $code !== $p['code'] && !val('SELECT id FROM products WHERE code = ? AND id <> ?', [$code, $id])) {
            $data['code'] = $code; $data['code_key'] = code_key($code);
        }
        update('products', $data, 'id = ?', [$id]);
    }

    // stock by location & batch
    q('DELETE FROM product_stock WHERE product_id = ?', [$id]);
    $locs = (array)input('st_loc', []);
    foreach ($locs as $i => $loc) {
        $loc = trim((string)$loc); if ($loc === '') continue;
        insert('product_stock', ['product_id' => $id, 'location' => $loc, 'rack' => trim((string)($_POST['st_rack'][$i] ?? '')) ?: null,
            'batch' => trim((string)($_POST['st_batch'][$i] ?? '')) ?: null, 'qty' => (int)($_POST['st_qty'][$i] ?? 0),
            'incoming' => !empty($_POST['st_inc'][$i]) ? 1 : 0, 'eta' => ($_POST['st_eta'][$i] ?? '') ?: null]);
    }

    // photos
    foreach (['photos' => 'photo', 'rooms' => 'room'] as $field => $kind) {
        if (empty($_FILES[$field]['name'][0])) continue;
        $sort = (int)val('SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id = ?', [$id]);
        foreach ($_FILES[$field]['name'] as $i => $nm) {
            $f = ['name' => $nm, 'tmp_name' => $_FILES[$field]['tmp_name'][$i], 'error' => $_FILES[$field]['error'][$i]];
            if ($path = save_upload($f)) insert('product_images', ['product_id' => $id, 'path' => $path, 'kind' => $kind, 'sort' => ++$sort]);
        }
    }
    flash($p ? 'Product saved.' : 'Product created — QR code generated.');
    redirect('admin/products/' . $id);
}

$p = $p ?: ['id' => 0, 'code' => '', 'name' => '', 'category_id' => $cats[0]['id'] ?? null, 'size' => '', 'finish' => '', 'thickness' => '', 'pcs_per_box' => '',
    'coverage_per_box' => '', 'weight_per_box' => '', 'where_to_use' => '', 'features' => '[]', 'description' => '', 'look_group' => '', 'unit' => 'sqft',
    'mrp' => '', 'offer_price' => '', 'dealer_price' => '', 'min_price' => '', 'purchase_cost' => '', 'gst' => 18, 'video_url' => '', 'visibility' => null,
    'qr_status' => 'generated', 'reprint_requested' => 0, 'code_key' => ''];
$isNew = !$p['id'];
$stock = $isNew ? [['location' => 'Godown A', 'rack' => '', 'batch' => '', 'qty' => '', 'incoming' => 0, 'eta' => '']] : product_stock($id);
$imgs = $isNew ? [] : product_images($id);
$feats = product_features($p);
$suggest = array_values(array_diff(['Stain resistant', 'Easy to clean', 'Rectified edges', 'Scratch resistant', 'Anti-skid on matt', 'Frost resistant', 'Digital print'], $feats));
$nextCode = $isNew ? next_product_code((int)$p['category_id']) : $p['code'];
$fmt = fn($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');

$title = $isNew ? 'Add product' : 'Edit product'; $active = 'products';
$actions = '<button class="btn gold" form="pf">' . icon('check') . 'Save product</button>';
$foot = '<script src="' . asset('js/qrcode.js') . '"></script>';
require ROOT . '/views/admin/top.php';
?>
<div class="steps">
  <a href="#basic" class="on"><i>1</i>Basic details</a><a href="#specs"><i>2</i>Specifications</a><a href="#price"><i>3</i>Price &amp; stock</a>
  <a href="#photos"><i>4</i>Photos &amp; video</a><a href="#qr"><i>5</i>Scan visibility &amp; QR</a>
</div>
<form id="pf" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="grid g-main" style="align-items:start">
<div class="stack">
  <div class="card" id="basic"><div class="hd"><h3>Basic details</h3></div><div class="bd fgrid">
    <div class="field s2"><label class="f">Product name</label><input name="name" required value="<?= e($p['name']) ?>" placeholder="e.g. Calacatta Gold Glossy"></div>
    <div class="field"><label class="f">Product code (auto)</label><input name="code" value="<?= e($nextCode) ?>" class="mono"></div>
    <div class="field"><label class="f">Category</label><select name="category_id" id="catSel">
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" data-unit="<?= $c['unit'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>><?= e(ucfirst($c['grp']) . ' › ' . $c['name']) ?></option><?php endforeach ?>
    </select></div>
    <div class="field"><label class="f">Size</label><input name="size" value="<?= e($p['size']) ?>" placeholder="600×1200 mm"></div>
    <div class="field"><label class="f">Finish</label><input name="finish" value="<?= e($p['finish']) ?>" placeholder="Glossy / Matt"></div>
    <div class="field"><label class="f">Thickness</label><input name="thickness" value="<?= e($p['thickness']) ?>" placeholder="9 mm"></div>
    <div class="field s2"><label class="f">“Same look” group <span class="muted">(links sizes/finishes of one design)</span></label><input name="look_group" value="<?= e($p['look_group']) ?>" placeholder="e.g. calacatta"></div>
  </div></div>

  <div class="card" id="specs"><div class="hd"><h3>Specifications</h3></div><div class="bd fgrid">
    <div class="field"><label class="f">Pieces per box</label><input name="pcs_per_box" type="number" value="<?= e($p['pcs_per_box']) ?>"></div>
    <div class="field"><label class="f">Coverage per box (sq.ft)</label><input name="coverage_per_box" type="number" step="0.01" value="<?= e($fmt($p['coverage_per_box'])) ?>"></div>
    <div class="field"><label class="f">Weight / box</label><input name="weight_per_box" value="<?= e($p['weight_per_box']) ?>" placeholder="28 kg"></div>
    <div class="field s3"><label class="f">Where to use</label><input name="where_to_use" value="<?= e($p['where_to_use']) ?>" placeholder="Floor · Wall · Living, bedroom"></div>
    <div class="field s3"><label class="f">Features (shown on scan)</label>
      <div class="row wrap" id="feats">
        <?php foreach ($feats as $f): ?><span class="chip b-gold"><input type="hidden" name="features[]" value="<?= e($f) ?>"><?= e($f) ?> <a href="#" onclick="this.parentNode.remove();return false">×</a></span><?php endforeach ?>
        <?php foreach ($suggest as $f): ?><a class="chip muted" href="#" onclick="addFeat(<?= e(json_encode($f)) ?>);this.remove();return false">+ <?= e($f) ?></a><?php endforeach ?>
        <input id="featIn" placeholder="+ Add" style="width:140px;padding:5px 10px;border-radius:20px" onkeydown="if(event.key==='Enter'){event.preventDefault();addFeat(this.value);this.value=''}">
      </div></div>
    <div class="field s3"><label class="f">Description (optional)</label><textarea name="description"><?= e($p['description']) ?></textarea></div>
  </div></div>

  <div class="card" id="price"><div class="hd"><h3>Price &amp; stock</h3><span class="small muted">per <span class="unitL"><?= unit_label($p['unit']) ?></span></span></div><div class="bd">
    <div class="price-box" style="margin-bottom:14px">
      <div class="p"><label class="f">MRP</label><input name="mrp" type="number" step="0.01" value="<?= e($fmt($p['mrp'])) ?>"></div>
      <div class="p"><label class="f">Offer price</label><input name="offer_price" type="number" step="0.01" value="<?= e($fmt($p['offer_price'])) ?>"></div>
      <div class="p"><label class="f">Dealer / contractor</label><input name="dealer_price" type="number" step="0.01" value="<?= e($fmt($p['dealer_price'])) ?>"></div>
      <div class="p"><label class="f">Minimum selling</label><input name="min_price" type="number" step="0.01" value="<?= e($fmt($p['min_price'])) ?>"></div>
      <div class="p admin"><label class="f red">Purchase cost (admin only)</label><input name="purchase_cost" type="number" step="0.01" value="<?= e($fmt($p['purchase_cost'])) ?>"></div>
    </div>
    <div class="row" style="margin-bottom:16px"><label class="f" style="margin:0">GST %</label><input name="gst" type="number" step="0.01" value="<?= e($fmt($p['gst'])) ?>" style="width:90px"></div>
    <table class="t" id="stockT"><thead><tr><th>Location</th><th>Rack</th><th>Batch / shade</th><th><span class="stockU"><?= $p['unit'] === 'pc' ? 'Pieces' : 'Boxes' ?></span></th><th>Incoming</th><th></th></tr></thead><tbody>
      <?php foreach ($stock as $s): ?>
        <tr><td><input name="st_loc[]" value="<?= e($s['location']) ?>"></td><td><input name="st_rack[]" value="<?= e($s['rack']) ?>"></td><td><input name="st_batch[]" value="<?= e($s['batch']) ?>"></td>
          <td><input name="st_qty[]" type="number" value="<?= e($s['qty']) ?>" style="width:90px"></td>
          <td class="nowrap"><select name="st_inc[]" style="width:80px" onchange="this.nextElementSibling.style.display=this.value=='1'?'':'none'"><option value="0">No</option><option value="1" <?= $s['incoming'] ? 'selected' : '' ?>>Yes</option></select><input type="date" name="st_eta[]" value="<?= e($s['eta']) ?>" style="width:140px;<?= $s['incoming'] ? '' : 'display:none' ?>"></td>
          <td><button type="button" class="btn ghost sm" onclick="this.closest('tr').remove()"><?= icon('trash') ?></button></td></tr>
      <?php endforeach ?>
    </tbody></table>
    <button type="button" class="btn sm" style="margin-top:10px" onclick="addStock()"><?= icon('plus') ?>Add location / batch</button>
  </div></div>

  <div class="card" id="photos"><div class="hd"><h3>Photos &amp; video</h3></div><div class="bd">
    <div class="row wrap" style="margin-bottom:14px">
      <?php foreach ($imgs as $im): ?>
        <div style="position:relative"><img src="<?= e(upload_url($im['path'])) ?>" style="width:110px;height:110px;object-fit:cover;border-radius:10px">
          <span class="badge b-grey" style="position:absolute;left:6px;bottom:6px"><?= $im['kind'] === 'room' ? 'Room' : 'Photo' ?></span>
          <button type="submit" form="delImg<?= $im['id'] ?>" class="btn sm" style="position:absolute;top:4px;right:4px;padding:3px 6px" title="Remove"><?= icon('x') ?></button></div>
      <?php endforeach ?>
      <?php if (!$imgs): ?><span class="muted small">No photos yet.</span><?php endif ?>
    </div>
    <div class="fgrid">
      <div class="field"><label class="f">Add product photos</label><input type="file" name="photos[]" accept="image/*" multiple></div>
      <div class="field"><label class="f">Add room / laying images</label><input type="file" name="rooms[]" accept="image/*" multiple></div>
      <div class="field"><label class="f">Laying video / room video (YouTube link)</label><input name="video_url" type="url" value="<?= e($p['video_url']) ?>" placeholder="https://youtu.be/…"></div>
    </div>
  </div></div>
</div>

<div class="stack" id="qr">
  <?php if (!$isNew): ?>
  <div class="card pad">
    <div class="row sb"><h3>QR code generated</h3><?= $p['qr_status'] === 'printed' ? '<span class="badge b-green">Printed</span>' : '<span class="badge b-gold">Not printed</span>' ?></div>
    <p class="small muted" style="margin:4px 0 12px">Created automatically on save · <span class="mono"><?= e(preg_replace('#^https?://#', '', qr_url($p))) ?></span></p>
    <div data-qr="<?= e(qr_url($p)) ?>" style="width:190px;height:190px;margin:0 auto 14px"></div>
    <div class="row" style="justify-content:center">
      <a class="btn sm" href="<?= url('admin/qr') ?>?ids[]=<?= $p['id'] ?>"><?= icon('print') ?>Print label</a>
      <button type="button" class="btn sm" onclick="downloadQrPng(<?= e(json_encode(qr_url($p))) ?>,'<?= e($p['code']) ?>')"><?= icon('download') ?>Download PNG</button>
      <a class="btn sm" href="<?= e(qr_url($p)) ?>" target="_blank"><?= icon('eye') ?>Open</a>
    </div>
    <?php if ($p['reprint_requested']): ?><div class="flash err" style="margin:12px 0 0">Staff requested a reprint of this QR (damaged label).</div><?php endif ?>
    <div class="c" style="margin-top:12px"><button type="submit" form="qrMark" class="btn ghost sm"><?= $p['qr_status'] === 'printed' ? 'Mark as not printed' : 'Mark as printed & pasted' ?></button></div>
  </div>
  <?php endif ?>
  <div class="card pad">
    <h3>Scan visibility</h3>
    <p class="small muted" style="margin:6px 0 12px"><?= $p['visibility'] ? 'This product has its own custom visibility.' : 'Uses the default template of its category.' ?></p>
    <?php if (!$isNew): ?><a class="btn sm block" href="<?= url('admin/visibility') ?>?product=<?= $p['id'] ?>"><?= icon('eye') ?>Edit what customers &amp; staff see</a><?php else: ?><p class="small muted">Save first to customise.</p><?php endif ?>
  </div>
  <?php if (!$isNew): ?>
  <div class="card pad"><h3>Danger zone</h3><p class="small muted" style="margin:6px 0 10px">Removing hides the product from scans, search and quotations.</p>
    <button type="submit" form="delP" class="btn sm red" onclick="return confirm('Remove this product?')"><?= icon('trash') ?>Remove product</button></div>
  <?php endif ?>
</div>
</div>
</form>
<?php if (!$isNew): ?>
  <?php foreach ($imgs as $im): ?><form id="delImg<?= $im['id'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="del_img"><input type="hidden" name="img" value="<?= $im['id'] ?>"></form><?php endforeach ?>
  <form id="qrMark" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="qr_printed"><input type="hidden" name="v" value="<?= $p['qr_status'] === 'printed' ? 'generated' : 'printed' ?>"></form>
  <form id="delP" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"></form>
<?php endif ?>
<script>
function addFeat(v){v=(v||'').trim();if(!v)return;const s=document.createElement('span');s.className='chip b-gold';s.innerHTML='<input type="hidden" name="features[]"> '+v.replace(/</g,'&lt;')+' <a href="#" onclick="this.parentNode.remove();return false">×</a>';s.querySelector('input').value=v;document.getElementById('featIn').before(s)}
function addStock(){const tb=document.querySelector('#stockT tbody');const tr=tb.rows[0]?tb.rows[0].cloneNode(true):null;if(!tr)return location.reload();tr.querySelectorAll('input').forEach(i=>i.value='');tr.querySelector('select').value='0';tr.querySelector('input[type=date]').style.display='none';tb.appendChild(tr)}
document.getElementById('catSel').addEventListener('change',function(){const pc=this.selectedOptions[0].dataset.unit==='pc';document.querySelectorAll('.unitL').forEach(e=>e.textContent=pc?'pc':'sq.ft');document.querySelectorAll('.stockU').forEach(e=>e.textContent=pc?'Pieces':'Boxes')});
</script>
<?php require ROOT . '/views/admin/bottom.php';
