<?php
$p = product_by_code($params[0]);
if (!$p) not_found('This product is not available.');
$base = 'q/' . $p['code_key'];
$err = '';
if (is_post()) {
    csrf_check();
    $mobile = clean_mobile((string)input('mobile'));
    $name = trim((string)input('name'));
    if (trim((string)input('website')) !== '') redirect($base . '/thanks');  // honeypot
    if ($name === '' || strlen($mobile) !== 10) $err = 'Please enter your name and a 10-digit mobile number.';
    elseif (($_SESSION['req_count'] ?? 0) > 6) $err = 'Thank you — we already have your requests. Our team will call you.';
    else {
        $_SESSION['req_count'] = ($_SESSION['req_count'] ?? 0) + 1;
        $pref = in_array(input('pref'), ['callback', 'whatsapp', 'visit'], true) ? input('pref') : 'callback';
        $city = trim((string)input('city')) ?: null;
        $c = row('SELECT * FROM customers WHERE mobile = ?', [$mobile]);
        if ($c) { update('customers', ['name' => $c['name'] ?: $name, 'city' => $c['city'] ?: $city, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$c['id']]); $cid = (int)$c['id']; }
        else $cid = insert('customers', ['name' => $name, 'mobile' => $mobile, 'city' => $city, 'source' => 'qr_' . $pref]);
        insert('leads', ['customer_id' => $cid, 'product_id' => $p['id'], 'channel' => $pref, 'area' => trim((string)input('area')) ?: null,
            'note' => $pref === 'visit' ? 'Will visit showroom' : ($pref === 'whatsapp' ? 'Prefers WhatsApp' : 'Call back')]);
        $_SESSION['lead_name'] = $name;
        redirect($base . '/thanks');
    }
}
$title = 'Get best price · ' . $p['name']; $back = url($base);
require ROOT . '/views/public/top.php';
?>
<form class="ab" method="post">
  <?= csrf_field() ?>
  <h2 style="font-size:20px">Get best price</h2>
  <p class="small muted" style="margin-bottom:14px">Our team will call or WhatsApp you</p>
  <div class="ptile" style="border:1px solid var(--line);border-radius:12px;padding:10px;margin-bottom:14px"><img src="<?= e(product_thumb($p)) ?>" alt=""><div><b class="small"><?= e($p['name']) ?></b><div class="xs muted"><?= e($p['size']) ?></div></div></div>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif ?>
  <div class="field"><label class="f">Name</label><input name="name" required value="<?= e(input('name')) ?>" autocomplete="name"></div>
  <div class="field"><label class="f">Mobile</label><input name="mobile" type="tel" inputmode="numeric" required value="<?= e(input('mobile')) ?>" placeholder="+91 XXXXX XXXXX" autocomplete="tel"></div>
  <div class="field"><label class="f">Area needed (approx.)</label><input name="area" value="<?= e(input('area')) ?>" placeholder="<?= $p['unit'] === 'pc' ? 'e.g. 2 pieces' : 'e.g. 500 sq.ft' ?>"></div>
  <div class="field"><label class="f">City</label><input name="city" value="<?= e(input('city')) ?>"></div>
  <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
  <input type="hidden" name="pref" id="pref" value="callback">
  <div class="row wrap" style="margin-bottom:16px;gap:6px" id="prefs">
    <button type="button" class="chip b-gold" data-v="callback" style="background:var(--ink);color:#fff;border-color:var(--ink)">Call me</button>
    <button type="button" class="chip" data-v="whatsapp">WhatsApp</button>
    <button type="button" class="chip" data-v="visit">I’ll visit showroom</button>
  </div>
  <button class="btn gold block lg">Send request</button>
</form>
<script>document.querySelectorAll('#prefs button').forEach(b=>b.onclick=()=>{document.querySelectorAll('#prefs button').forEach(x=>{const on=x===b;x.style.background=on?'#1c1a17':'#fff';x.style.color=on?'#fff':'';x.style.borderColor=on?'#1c1a17':''});document.getElementById('pref').value=b.dataset.v})</script>
<?php require ROOT . '/views/public/bottom.php';
