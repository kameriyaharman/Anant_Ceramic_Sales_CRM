<?php
/** @var string $title  @var string $active  @var string $actions (html) */
$active = $active ?? '';
$pendingApprovals = (int)val("SELECT COUNT(*) FROM quotations WHERE status = 'approval'");
$newLeads = (int)val("SELECT COUNT(*) FROM leads WHERE status = 'new'");
$reprints = (int)val("SELECT COUNT(*) FROM products WHERE reprint_requested = 1");
$bell = $pendingApprovals + $newLeads;
$nav = [
    'MAIN' => [
        ['admin', 'dash', 'Dashboard', 'dashboard', 0],
        ['admin/products', 'box', 'Products', 'products', 0],
        ['admin/visibility', 'eye', 'Scan visibility', 'visibility', 0],
        ['admin/qr', 'qr', 'QR codes & labels', 'qr', $reprints],
        ['admin/quotations', 'doc', 'Quotations', 'quotations', $pendingApprovals],
    ],
    'SALES' => [
        ['admin/staff', 'users', 'Staff', 'staff', 0],
        ['admin/customers', 'user', 'Customers & leads', 'customers', $newLeads],
        ['admin/reports', 'chart', 'Reports', 'reports', 0],
        ['admin/settings', 'gear', 'Settings', 'settings', 0],
    ],
];
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · Anant Ceramic CRM</title>
<link rel="icon" href="<?= asset('img/icon.svg') ?>">
<?= fonts() ?>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<?= $head ?? '' ?>
</head><body>
<div class="shell">
  <aside class="side">
    <a href="<?= url('admin') ?>" style="padding:0;background:none"><?= logo() ?></a>
    <div class="tag">SALES CRM</div>
    <?php foreach ($nav as $sec => $navItems): ?>
      <div class="sec"><?= $sec ?></div>
      <?php foreach ($navItems as [$href, $ic, $label, $key, $cnt]): ?>
        <a href="<?= url($href) ?>" class="<?= $active === $key ? 'on' : '' ?>"><?= icon($ic) ?><?= e($label) ?><?php if ($cnt): ?><span class="cnt"><?= $cnt ?></span><?php endif ?></a>
      <?php endforeach ?>
    <?php endforeach ?>
    <div class="sec">ACCOUNT</div>
    <a href="<?= url('app') ?>" target="_blank"><?= icon('scan') ?>Open staff app</a>
    <a href="<?= url('admin/logout') ?>"><?= icon('logout') ?>Log out</a>
  </aside>
  <main class="main">
    <div class="top">
      <button class="btn sm menu-btn" onclick="document.body.classList.toggle('side-open')"><?= icon('menu') ?></button>
      <h1><?= e($title) ?></h1>
      <?= $actions ?? '' ?>
      <form class="search" action="<?= url('admin/search') ?>"><?= icon('search') ?><input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search product, code, quotation…"></form>
      <a class="bell" href="<?= url($pendingApprovals ? 'admin/quotations?status=approval' : 'admin/customers') ?>" title="<?= $pendingApprovals ?> approvals · <?= $newLeads ?> new leads"><?= icon('bell') ?><?php if ($bell): ?><i><?= $bell ?></i><?php endif ?></a>
      <div class="me" title="<?= e(admin()['email'] ?? '') ?>">AD</div>
    </div>
    <?php if ($f = flash()): ?><div class="flash <?= $f[1] ?>"><?= e($f[0]) ?></div><?php endif ?>
