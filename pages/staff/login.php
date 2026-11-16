<?php
if (staff_user()) redirect('app');
$err = '';
if (is_post()) {
    csrf_check();
    $_SESSION['pin_tries'] = ($_SESSION['pin_tries'] ?? 0) + 1;
    $mobile = clean_mobile((string)input('mobile'));
    if ($_SESSION['pin_tries'] > 8) $err = 'Too many attempts. Please wait or ask admin.';
    else {
        $s = row('SELECT * FROM staff WHERE mobile = ?', [$mobile]);
        if ($s && $s['status'] !== 'active') $err = 'Your login is blocked. Please contact admin.';
        elseif ($s && password_verify((string)input('pin'), $s['pin_hash'])) {
            session_regenerate_id(true);
            $_SESSION['staff_id'] = $s['id']; $_SESSION['pin_tries'] = 0;
            update('staff', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$s['id']]);
            redirect('app');
        } else $err = 'Mobile number or PIN is wrong.';
    }
}
$title = 'Staff login'; $noHeader = true; $noNav = true;
require ROOT . '/views/staff/top.php';
?>
<div class="login-hero">
  <div class="emb"><?= str_replace('<svg ', '<svg style="width:100%;height:100%" ', logo_mark()) ?></div>
  <div style="color:var(--gold-d);display:flex;justify-content:center"><div class="logo"><div class="wm"><b>ANANT</b><i>CERAMIC</i></div></div></div>
  <div class="xs b" style="letter-spacing:.24em;color:var(--gold);margin-top:10px">STAFF APP</div>
</div>
<form class="ab" method="post" style="padding:20px 24px">
  <?= csrf_field() ?>
  <h2 style="font-size:21px">Staff login</h2>
  <p class="small muted" style="margin:4px 0 18px">Only staff added by admin can log in</p>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif ?>
  <div class="field"><label class="f">Mobile number</label><input type="tel" name="mobile" inputmode="numeric" autocomplete="username" value="<?= e(input('mobile')) ?>" placeholder="+91 98XXX XXXXX" required></div>
  <div class="field"><label class="f">4-digit PIN</label><input type="password" name="pin" inputmode="numeric" maxlength="4" pattern="\d{4}" autocomplete="current-password" placeholder="••••" required style="letter-spacing:.4em;font-size:18px"></div>
  <button class="btn gold block lg">Login</button>
  <p class="c small muted" style="margin-top:16px">Forgot PIN? Ask admin to reset</p>
</form>
<?php require ROOT . '/views/staff/bottom.php';
