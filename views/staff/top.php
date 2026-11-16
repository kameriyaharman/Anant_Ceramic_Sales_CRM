<?php
/** @var string $title @var ?string $back @var string $right @var bool $brand */
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1e1b17"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes">
<title><?= e($title ?? 'Anant Staff') ?> · Anant Staff</title>
<link rel="manifest" href="<?= url('manifest.webmanifest') ?>"><link rel="icon" href="<?= asset('img/icon.svg') ?>"><link rel="apple-touch-icon" href="<?= asset('img/icon-192.png') ?>">
<?= fonts() ?>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>"><link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?= $head ?? '' ?>
</head><body class="app"><div class="appw">
<?php if (empty($noHeader)): ?>
<header class="ahd">
  <?php if (!empty($brand)): ?><div style="color:var(--gold-d)"><?= logo() ?></div><div class="grow"></div>
  <?php else: ?><a class="ib" href="<?= e($back ?? url('app')) ?>"><?= icon('back') ?></a><h1><?= e($title ?? '') ?></h1><?php endif ?>
  <?= $right ?? '<span class="ib"></span>' ?>
</header>
<?php endif ?>
<?php if ($f = flash()): ?><div class="ab" style="padding-bottom:0"><div class="flash <?= $f[1] ?>" style="margin:0"><?= e($f[0]) ?></div></div><?php endif ?>
