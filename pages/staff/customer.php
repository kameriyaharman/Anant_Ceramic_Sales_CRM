<?php
$me = require_staff();
// mobile lookup (AJAX)
if (input('lookup')) {
    $c = row('SELECT name, city, address, architect FROM customers WHERE mobile = ?', [clean_mobile((string)input('lookup'))]);
    json_out(['found' => (bool)$c, 'c' => $c]);
}
$q = current_draft($me);
if (!$q) redirect('app/quote');
$items = quote_items((int)$q['id']);
if (!$items) redirect('app/quote');

if (is_post()) {
    csrf_check();
    $mobile = clean_mobile((string)input('mobile'));
    $name = trim((string)input('name'));
    if (strlen($mobile) !== 10 || $name === '') { flash('Enter the customer name and a 10-digit mobile.', 'err'); redirect('app/quote/customer'); }
    $addr = trim((string)input('address')) ?: null;
    $arch = trim((string)input('architect')) ?: null;
    $c = row('SELECT * FROM customers WHERE mobile = ?', [$mobile]);
    if ($c) {
        update('customers', ['name' => $name, 'address' => $addr ?: $c['address'], 'architect' => $arch ?: $c['architect'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$c['id']]);
        $cid = (int)$c['id'];
        q('UPDATE leads SET status = "converted" WHERE customer_id = ? AND status IN ("new","contacted")', [$cid]);
    } else $cid = insert('customers', ['name' => $name, 'mobile' => $mobile, 'address' => $addr, 'architect' => $arch, 'city' => trim((string)input('city')) ?: null, 'source' => 'quotation']);

    // approvals
    $needs = false;
    foreach ($items as $i) {
        $why = approval_reason_for($me, ['offer_price' => $i['offer_price'], 'min_price' => $i['min_price']], (float)$i['rate']);
        if ($why && $i['approval'] === 'approved') continue;      // admin already approved this rate
        if ($why) { $needs = true; update('quotation_items', ['approval' => 'pending', 'approval_reason' => $i['approval_reason'] ?: $why], 'id = ?', [$i['id']]); }
        elseif ($i['approval'] !== 'none') update('quotation_items', ['approval' => 'none', 'counter_rate' => null], 'id = ?', [$i['id']]);
    }
    $follow = (string)input('follow_up_at');
    update('quotations', [
        'customer_id' => $cid, 'delivery_address' => $addr, 'architect' => $arch, 'delivery' => input('delivery') ?: null,
        'follow_up_at' => $follow ? date('Y-m-d H:i:s', strtotime($follow)) : null,
        'valid_till' => date('Y-m-d', strtotime('+' . (int)setting('quote_validity', '7') . ' days')),
        'status' => $needs ? 'approval' : 'ready', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [$q['id']]);
    recalc_quote((int)$q['id']);
    unset($_SESSION['draft_id']);
    flash($needs ? 'Quotation created — sent to admin for discount approval.' : 'Quotation created.');
    redirect('app/quotes/' . $q['id']);
}
$c = $q['customer_id'] ? row('SELECT * FROM customers WHERE id = ?', [$q['customer_id']]) : null;
$title = 'Customer details'; $back = url('app/quote'); $nav = 'quotes';
require ROOT . '/views/staff/top.php';
?>
<form class="ab" method="post" id="f" style="padding-bottom:130px">
  <?= csrf_field() ?>
  <div class="field"><label class="f">Customer mobile</label><input type="tel" name="mobile" id="mob" inputmode="numeric" value="<?= e($c['mobile'] ?? '') ?>" placeholder="+91 98XXX XXXXX" required></div>
  <div id="found" class="small green b" style="margin:-6px 0 12px"></div>
  <div class="field"><label class="f">Name</label><input name="name" id="nm" value="<?= e($c['name'] ?? '') ?>" required></div>
  <div class="field"><label class="f">Site / delivery address</label><input name="address" id="ad" value="<?= e($c['address'] ?? '') ?>"></div>
  <div class="field"><label class="f">City</label><input name="city" id="ct" value="<?= e($c['city'] ?? '') ?>"></div>
  <div class="field"><label class="f">Architect / contractor (optional)</label><input name="architect" id="ar" value="<?= e($c['architect'] ?? '') ?>"></div>
  <div class="field"><label class="f">Delivery</label><select name="delivery"><option>Required · transport extra</option><option>Required · transport included</option><option>Customer pickup</option></select></div>
  <div class="field"><label class="f">Follow-up reminder</label><input type="datetime-local" name="follow_up_at" value="<?= date('Y-m-d\T11:00', strtotime('+2 days')) ?>"></div>
  <div class="tot"><div><span><?= count($items) ?> items · Sub-total</span><b><?= money($q['subtotal']) ?></b></div><div><span>GST</span><b><?= money($q['gst_amount']) ?></b></div><div class="g"><span>Grand total</span><span><?= inr($q['total']) ?></span></div></div>
</form>
<div class="sticky-cta"><button class="btn gold block lg" form="f"><?= icon('check') ?>Create quotation</button></div>
<script>
const mob=document.getElementById('mob');let last='';
mob.addEventListener('input',async()=>{const d=mob.value.replace(/\D/g,'').slice(-10);if(d.length!==10||d===last)return;last=d;
  const r=await fetch(window.BASE+'/app/quote/customer?lookup='+d).then(r=>r.json()).catch(()=>({}));
  const f=document.getElementById('found');
  if(r.found){f.textContent='Existing customer found — '+(r.c.name||'');['nm:name','ad:address','ct:city','ar:architect'].forEach(x=>{const[i,k]=x.split(':');const el=document.getElementById(i);if(!el.value&&r.c[k])el.value=r.c[k]})}else f.textContent='';
});
</script>
<?php require ROOT . '/views/staff/bottom.php';
