<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#ffffff">
<title><?= e($title ?? 'Anant Ceramic') ?> · Anant Ceramic</title>
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif ?>
<meta property="og:title" content="<?= e($title ?? 'Anant Ceramic') ?>">
<link rel="icon" href="<?= asset('img/icon.svg') ?>">
<?= fonts() ?>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>"><link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<style>.appw{padding-bottom:30px}.pub-hd{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #f1ece4}.pub-hd .logo svg{width:30px;height:30px}.pub-hd .logo .wm b{font-size:14px}.pub-hd .logo .wm i{font-size:6px}</style>
</head><body class="app"><div class="appw">
<?php if (empty($noHeader)): ?><header class="pub-hd"><?php if (!empty($back)): ?><a href="<?= e($back) ?>" style="width:28px"><?= icon('back') ?></a><?php endif ?><a href="<?= e(setting('company_website', '#')) ?>" style="color:var(--gold-d)"><?= logo() ?></a><span style="width:28px"></span></header><?php endif ?>
