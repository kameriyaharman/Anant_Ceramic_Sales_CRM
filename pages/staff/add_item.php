<?php
$me = require_staff();
if (!$me['perm']['quote']) { flash('Your role cannot create quotations.', 'err'); redirect('app'); }
$p = product_by_code($params[0]);
if (!$p) not_found('Product not found');
$isPc = $p['unit'] === 'pc';
$cov = (float)($p['coverage_per_box'] ?: 0);
if (!$isPc && $cov <= 0) $isPc = false;

$item = null; $quote = null;
if ($iid = (int)input('item')) {
    $item = row('SELECT i.* FROM quotation_items i WHERE i.id = ? AND i.product_id = ?', [$iid, $p['id']]);
    $quote = $item ? row('SELECT * FROM quotations WHERE id = ?', [$item['quotation_id']]) : null;
    if (!$quote || !can_see_quote($me, $quote) || !in_array($quote['status'], ['draft', 'approval', 'ready'], true)) { flash('This item can no longer be edited.', 'err'); redirect('app/quotes'); }
}

if (is_post()) {
    csrf_check();
    $rate = round((float)input('rate'), 2);
    $note = trim((string)input('note')) ?: null;
    if ($isPc) { $boxes = max(1, (int)input('pcs')); $area = 0; $amount = $boxes * $rate; }
    else {
        $mode = input('mode');
        if ($mode === 'boxes') $boxes = max(1, (int)input('boxes'));
        else {
            $need = $mode === 'room' ? (float)input('len') * (float)input('wid') : (float)input('area');
            if (input('wastage')) $need *= 1 + (float)setting('wastage_pct', '5') / 100;
            $boxes = $cov > 0 ? (int)ceil(round($need / $cov, 4)) : 0;
        }
        if ($boxes < 1) { flash('Enter the area or number of boxes.', 'err'); redirect('app/p/' . $p['code'] . '/add' . ($item ? '?item=' . $item['id'] : '')); }
        $area = round($boxes * $cov, 2); $amount = round($area * $rate, 2);
    }
    if ($rate <= 0) { flash('Enter a valid rate.', 'err'); redirect('app/p/' . $p['code'] . '/add'); }
    $reason = approval_reason_for($me, $p, $rate);
    $staffReason = trim((string)input('reason'));
    $data = ['boxes' => $boxes, 'area' => $area, 'rate' => $rate, 'amount' => $amount, 'gst' => $p['gst'], 'discount_pct' => discount_pct((float)$p['offer_price'], $rate), 'note' => $note,
        'approval_reason' => $reason ? ($staffReason ?: $reason) : null, 'counter_rate' => null];
    if ($item) {
        // in a draft, approval is requested when the quotation is created; otherwise ask right away
        $data['approval'] = ($reason && $quote['status'] !== 'draft') ? 'pending' : 'none';
        update('quotation_items', $data, 'id = ?', [$item['id']]);
        recalc_quote((int)$quote['id']);
        if ($quote['status'] !== 'draft') {
            if ($reason && $quote['status'] === 'ready') update('quotations', ['status' => 'approval'], 'id = ?', [$quote['id']]);
            refresh_quote_status((int)$quote['id']);
            redirect('app/quotes/' . $quote['id']);
        }
        redirect('app/quote');
    }
    $q = ensure_draft($me);
    $sort = (int)val('SELECT COALESCE(MAX(sort),0) + 1 FROM quotation_items WHERE quotation_id = ?', [$q['id']]);
    insert('quotation_items', $data + ['quotation_id' => $q['id'], 'product_id' => $p['id'], 'approval' => 'none', 'sort' => $sort]);
    recalc_quote((int)$q['id']);
    flash($p['name'] . ' added · ' . money($amount));
    redirect(input('next') === 'scan' ? 'app/scan' : 'app/quote');
}

$rate0 = $item ? (float)$item['rate'] : (float)$p['offer_price'];
$title = 'Add to quotation'; $back = url('app/p/' . $p['code']);
require ROOT . '/views/staff/top.php';
$stockN = stock_total((int)$p['id']);
?>
<form class="ab" method="post" id="f" style="padding-bottom:120px">
  <?= csrf_field() ?>
  <div class="ptile" style="padding-top:0"><img src="<?= e(product_thumb($p)) ?>"><div class="grow"><b><?= e($p['name']) ?></b>
    <div class="xs muted"><?= e($p['size']) ?> · <?= inr($p['offer_price']) ?> offer<?= $me['perm']['min'] ? ' · min ' . inr($p['min_price']) : '' ?></div><div class="xs <?= $stockN ? 'green' : 'red' ?>"><?= number_format($stockN) ?> <?= stock_unit($p) ?> in stock</div></div></div>

  <div class="sect">Quantity</div>
  <?php if ($isPc): ?>
    <div class="field"><label class="f">Pieces</label><input type="number" name="pcs" id="pcs" min="1" value="<?= $item ? (int)$item['boxes'] : 1 ?>" inputmode="numeric"></div>
  <?php else: ?>
    <input type="hidden" name="mode" id="mode" value="<?= $item ? 'boxes' : 'area' ?>">
    <div class="seg"><button type="button" data-m="area" class="<?= $item ? '' : 'on' ?>">Area (sq.ft)</button><button type="button" data-m="boxes" class="<?= $item ? 'on' : '' ?>">Boxes</button><button type="button" data-m="room">Room size</button></div>
    <div data-p="area" class="<?= $item ? 'hidden' : '' ?>"><div class="field"><label class="f">Area needed (sq.ft)</label><input type="number" step="0.01" name="area" id="area" inputmode="decimal" placeholder="e.g. 640"></div></div>
    <div data-p="boxes" class="<?= $item ? '' : 'hidden' ?>"><div class="field"><label class="f">Boxes</label><input type="number" name="boxes" id="boxes" min="1" value="<?= $item ? (int)$item['boxes'] : '' ?>" inputmode="numeric"></div></div>
    <div data-p="room" class="hidden"><div class="row"><div class="field grow"><label class="f">Length (ft)</label><input type="number" step="0.01" name="len" id="len" inputmode="decimal"></div><div class="field grow"><label class="f">Width (ft)</label><input type="number" step="0.01" name="wid" id="wid" inputmode="decimal"></div></div></div>
    <label class="row small" id="wl"><input type="checkbox" name="wastage" id="wastage" value="1"> Add <?= e(setting('wastage_pct', '5')) ?>% wastage</label>
    <div class="calc" id="calc">—</div>
  <?php endif ?>

  <div class="field"><label class="f">Rate per <?= unit_label($p['unit']) ?></label><input type="number" step="0.01" name="rate" id="rate" value="<?= e(num($rate0)) ?>" inputmode="decimal" style="font-size:18px;font-weight:600"></div>
  <div id="disc" class="small"></div>
  <div class="field hidden" id="reasonF" style="margin-top:10px"><label class="f">Reason for admin (e.g. bulk builder order)</label><input name="reason" value="<?= e($item['approval_reason'] ?? '') ?>"></div>
  <div class="field" style="margin-top:12px"><label class="f">Note on item (optional)</label><input name="note" value="<?= e($item['note'] ?? '') ?>" placeholder="e.g. Living + dining floor"></div>
</form>
<div class="sticky-cta"><div class="row">
  <?php if (!$item): ?><button class="btn lg" form="f" name="next" value="scan" title="Add & scan next"><?= icon('scan') ?></button><?php endif ?>
  <button class="btn gold lg grow" form="f" id="addBtn"><?= $item ? 'Update' : 'Add' ?> · <span id="amt">—</span></button></div></div>
<script>
const P={pc:<?= $isPc ? 'true' : 'false' ?>,cov:<?= $cov ?>,offer:<?= (float)$p['offer_price'] ?>,min:<?= (float)$p['min_price'] ?>,max:<?= (float)$me['max_discount'] ?>,belowMin:<?= $me['perm']['below_min'] ? 'true' : 'false' ?>,waste:<?= (float)setting('wastage_pct', '5') ?>,showMin:<?= $me['perm']['min'] ? 'true' : 'false' ?>};
const $=id=>document.getElementById(id);
const fmt=n=>'₹'+Number(n).toLocaleString('en-IN',{maximumFractionDigits:2});
document.querySelectorAll('.seg button').forEach(b=>b.onclick=()=>{document.querySelectorAll('.seg button').forEach(x=>x.classList.toggle('on',x===b));
  document.querySelectorAll('[data-p]').forEach(d=>d.classList.toggle('hidden',d.dataset.p!==b.dataset.m));$('mode').value=b.dataset.m;$('wl').style.display=b.dataset.m==='boxes'?'none':'';calc()});
function calc(){
  const rate=parseFloat($('rate').value)||0;let amount=0;
  if(P.pc){amount=(parseInt($('pcs').value)||0)*rate}
  else{const m=$('mode').value;let boxes=0,need=0;
    if(m==='boxes')boxes=parseInt($('boxes').value)||0;
    else{need=m==='room'?(parseFloat($('len').value)||0)*(parseFloat($('wid').value)||0):(parseFloat($('area').value)||0);
      if($('wastage').checked)need*=1+P.waste/100;boxes=P.cov?Math.ceil(+(need/P.cov).toFixed(4)):0}
    const area=+(boxes*P.cov).toFixed(2);amount=area*rate;
    $('calc').innerHTML=boxes?(need?'<b>'+(+need.toFixed(2))+' sq.ft</b> = ':'')+'<b>'+boxes+' boxes</b> ('+area+' sq.ft)'+(need?' · incl. rounding':''):'Enter quantity';
  }
  $('amt').textContent=fmt(amount.toFixed(2));
  const d=P.offer?((P.offer-rate)/P.offer*100):0;const below=rate<P.min-0.001&&!P.belowMin;const over=d>P.max+0.001;
  if(over||below){$('disc').className='warn-line';$('disc').innerHTML='⚠ '+(over?'Discount '+d.toFixed(1)+'% is above your '+P.max+'% limit':'')+(over&&below?' · ':'')+(below?'Rate is below minimum'+(P.showMin?' '+fmt(P.min):''):'')+' — will go to admin for approval';$('reasonF').classList.remove('hidden')}
  else{$('disc').className='ok-line';$('disc').innerHTML=d>0?'Discount '+d.toFixed(1)+'% · within your '+P.max+'% limit ✓':'At offer price ✓';$('reasonF').classList.add('hidden')}
}
document.querySelectorAll('#f input').forEach(i=>i.addEventListener('input',calc));calc();
</script>
<?php require ROOT . '/views/staff/bottom.php';
