<?php
if (admin()) redirect('admin');
$err = '';
if (is_post()) {
    csrf_check();
    $email = trim((string)input('email'));
    $pass = (string)input('password');
    $_SESSION['login_tries'] = ($_SESSION['login_tries'] ?? 0) + 1;
    if ($_SESSION['login_tries'] > 10) { $err = 'Too many attempts. Please wait a few minutes.'; }
    else {
        $a = row('SELECT * FROM admins WHERE email = ?', [$email]);
        if ($a && password_verify($pass, $a['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $a['id'];
            $_SESSION['login_tries'] = 0;
            redirect('admin');
        }
        $err = 'Wrong email or password.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin login · Anant Ceramic CRM</title><link rel="icon" href="<?= asset('img/icon.svg') ?>"><?= fonts() ?>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body><div class="login-wrap"><form class="login-card" method="post">
  <?= csrf_field() ?>
  <div style="display:flex;justify-content:center;color:var(--gold-d);margin-bottom:6px"><?= logo() ?></div>
  <div class="c xs b" style="letter-spacing:.2em;color:var(--gold);margin-bottom:24px">SALES CRM · ADMIN</div>
  <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif ?>
  <div class="field"><label class="f">Email</label><input type="email" name="email" required autofocus value="<?= e(input('email')) ?>"></div>
  <div class="field"><label class="f">Password</label><input type="password" name="password" required></div>
  <button class="btn gold block lg">Log in</button>
  <p class="c small muted" style="margin-top:18px">Staff? <a class="gold b" href="<?= url('app/login') ?>">Open the staff app</a></p>
</form></div></body></html>
