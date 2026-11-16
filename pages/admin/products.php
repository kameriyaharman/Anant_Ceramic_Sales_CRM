<?php
require_admin();
$grp = (string)input('grp', '');
$filter = (string)input('f', '');
$qs = trim((string)input('q', ''));
$page = max(1, (int)input('page', 1));
$per = 50;
$low = (int)setting('low_stock_boxes', '40');

$where = ['p.active = 1']; $par = [];
if ($grp !== '') { $where[] = 'c.grp = ?'; $par[] = $grp; }
if ($qs !== '') { $where[] = '(p.name LIKE ? OR p.code LIKE ? OR p.size LIKE ?)'; array_push($par, "%$qs%", "%$qs%", "%$qs%"); }
$stockSub = '(SELECT COALESCE(SUM(qty),0) FROM product_stock ps WHERE ps.product_id = p.id AND ps.incoming = 0)';
if ($filter === 'qr') $where[] = 'p.qr_status <> "printed"';
if ($filter === 'low') { $where[] = "$stockSub < ?"; $par[] = $low; }
$w = implode(' AND ', $where);

$total = (int)val("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $w", $par);
$list = rows("SELECT p.*, c.grp, c.name cat_name, $stockSub stock FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $w ORDER BY p.code LIMIT $per OFFSET " . (($page - 1) * $per), $par);

$counts = ['' => (int)val('SELECT COUNT(*) FROM products WHERE active = 1')];
foreach (rows('SELECT c.grp, COUNT(*) n FROM products p JOIN categories c ON c.id = p.category_id WHERE p.active = 1 GROUP BY c.grp') as $r) $counts[$r['grp']] = $r['n'];
$qrNot = (int)val('SELECT COUNT(*) FROM products WHERE active = 1 AND qr_status <> "printed"');
$lowN = (int)val("SELECT COUNT(*) FROM products p WHERE p.active = 1 AND $stockSub < ?", [$low]);

$link = fn(array $over) => url('admin/products') . '?' . http_build_query(array_filter(array_merge(['grp' => $grp, 'f' => $filter, 'q' => $qs], $over), fn($v) => $v !== '' && $v !== null));

$title = 'Products'; $active = 'products';
$actions = '<a class="btn" href="' . url('admin/products/export') . '">' . icon('download') . 'Export</a>'
    . '<button class="btn" onclick="openModal(\'bulk\')">' . icon('upload') . 'Bulk upload (Excel)</button>'
    . '<a class="btn gold" href="' . url('admin/products/new') . '">' . icon('plus') . 'Add product</a>';
$foot = '<script src="' . asset('js/xlsx.mini.min.js') . '"></script>';
require ROOT . '/views/admin/top.php';
?>
<div class="row sb wrap" style="margin-bottom:14px">
  <div class="tabs">
    <?php foreach (['' => 'All', 'tiles' => 'Tiles', 'sanitary' => 'Sanitary', 'fittings' => 'Fittings'] as $k => $l): ?>
      <a class="<?= $grp === $k ? 'on' : '' ?>" href="<?= $link(['grp' => $k, 'page' => null]) ?>"><?= $l ?><span class="n"><?= number_format($counts[$k] ?? 0) ?></span></a>
    <?php endforeach ?>
  </div>
  <div class="row">
    <a class="chip <?= $filter === 'qr' ? 'b-gold' : '' ?>" href="<?= $link(['f' => $filter === 'qr' ? '' : 'qr']) ?>">QR not printed <b><?= $qrNot ?></b></a>
    <a class="chip <?= $filter === 'low' ? 'b-red' : '' ?>" href="<?= $link(['f' => $filter === 'low' ? '' : 'low']) ?>">Low stock <b><?= $lowN ?></b></a>
  </div>
</div>

<form id="sel" method="get" action="<?= url('admin/qr') ?>">
<div class="card">
  <div class="tbl-wrap"><table class="t">
    <thead><tr><th style="width:30px"><input type="checkbox" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked);selCount()"></th>
      <th>Product</th><th>Code</th><th>Size</th><th>Offer price</th><th>Stock</th><th>Visibility</th><th>QR</th></tr></thead>
    <tbody>
    <?php foreach ($list as $p): ?>
      <tr class="click" onclick="if(event.target.type!=='checkbox')location='<?= url('admin/products/' . $p['id']) ?>'">
        <td><input type="checkbox" class="pick" name="ids[]" value="<?= $p['id'] ?>" onchange="selCount()"></td>
        <td><div class="row"><img class="thumb" src="<?= e(product_thumb($p)) ?>" alt=""><div><div class="b"><?= e($p['name']) ?></div><div class="xs muted"><?= e(cat_label($p)) ?></div></div></div></td>
        <td class="mono"><?= e($p['code']) ?></td>
        <td class="small"><?= e($p['size']) ?></td>
        <td class="nowrap"><?= inr($p['offer_price']) ?> <span class="xs muted">/ <?= unit_label($p['unit']) ?></span></td>
        <td class="nowrap <?= $p['stock'] < $low ? 'red b' : '' ?>"><?= number_format((int)$p['stock']) ?> <?= $p['unit'] === 'pc' ? 'pc' : 'bx' ?></td>
        <td><span class="small <?= $p['visibility'] ? 'gold b' : 'muted' ?>"><?= $p['visibility'] ? 'Custom' : 'Default template' ?></span></td>
        <td><?= $p['qr_status'] === 'printed' ? '<span class="badge b-green">Printed</span>' : '<span class="badge b-gold">Not printed</span>' ?><?= $p['reprint_requested'] ? ' <span class="badge b-red">Reprint</span>' : '' ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table></div>
  <?php if (!$list): ?><div class="empty">No products found. <a class="gold b" href="<?= url('admin/products/new') ?>">Add a product</a> or use bulk upload.</div><?php endif ?>
  <div class="row sb" style="padding:12px 16px;border-top:1px solid var(--line)">
    <button class="btn dark sm" id="printSel" disabled><?= icon('qr') ?>Print QR for selected <span id="selN"></span></button>
    <div class="row small muted">
      <?= $total ? (($page - 1) * $per + 1) . '–' . min($total, $page * $per) . ' of ' . number_format($total) : '' ?>
      <?php if ($page > 1): ?><a class="btn sm" href="<?= $link(['page' => $page - 1]) ?>">‹</a><?php endif ?>
      <?php if ($page * $per < $total): ?><a class="btn sm" href="<?= $link(['page' => $page + 1]) ?>">›</a><?php endif ?>
    </div>
  </div>
</div>
</form>

<div class="modal" id="bulk"><div class="box">
  <div class="row sb" style="margin-bottom:14px"><h2>Bulk upload products (Excel)</h2><button class="btn ghost sm" onclick="closeModal('bulk')"><?= icon('x') ?></button></div>
  <p class="small muted" style="margin-bottom:12px">Upload an .xlsx or .csv file. Rows with an existing <b>code</b> are updated; rows without a code get a new code automatically. A QR is generated for every product.</p>
  <p class="small" style="margin-bottom:14px"><a class="gold b" href="<?= url('admin/products/export?template=1') ?>"><?= icon('download') ?> Download the Excel template</a></p>
  <label class="dropzone" id="dz"><input type="file" id="xfile" accept=".xlsx,.xls,.csv" hidden><b>Choose file</b><div class="small muted">or drop it here</div></label>
  <div id="bulkInfo" class="small" style="margin-top:12px"></div>
  <div class="row" style="justify-content:flex-end;margin-top:16px"><button class="btn gold" id="bulkGo" disabled>Upload</button></div>
</div></div>

<script>
function selCount(){const n=document.querySelectorAll('.pick:checked').length;document.getElementById('selN').textContent=n?'('+n+')':'';document.getElementById('printSel').disabled=!n}
let bulkRows=[];
document.getElementById('xfile').addEventListener('change',e=>readBulk(e.target.files[0]));
const dz=document.getElementById('dz');
dz.addEventListener('dragover',e=>{e.preventDefault()});dz.addEventListener('drop',e=>{e.preventDefault();readBulk(e.dataTransfer.files[0])});
function readBulk(f){if(!f)return;const r=new FileReader();r.onload=ev=>{const wb=XLSX.read(ev.target.result,{type:'array'});const ws=wb.Sheets[wb.SheetNames[0]];
 bulkRows=XLSX.utils.sheet_to_json(ws,{defval:''});document.getElementById('bulkInfo').innerHTML='<b>'+f.name+'</b> — '+bulkRows.length+' rows found';document.getElementById('bulkGo').disabled=!bulkRows.length};r.readAsArrayBuffer(f)}
document.getElementById('bulkGo').onclick=async function(){this.disabled=true;this.textContent='Uploading…';
 const res=await post('admin/products/bulk',{rows:JSON.stringify(bulkRows)});
 document.getElementById('bulkInfo').innerHTML=res.ok?'<span class="green b">'+res.created+' added, '+res.updated+' updated.</span>'+(res.errors.length?'<br><span class="red">'+res.errors.slice(0,8).join('<br>')+'</span>':''):'<span class="red">'+(res.error||'Failed')+'</span>';
 this.textContent='Upload';if(res.ok)setTimeout(()=>location.reload(),1600)}
</script>
<?php require ROOT . '/views/admin/bottom.php';
