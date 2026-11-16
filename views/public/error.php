<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Not found') ?> · Anant Ceramic</title>
<link rel="stylesheet" href="<?= asset('css/base.css') ?>"></head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div style="text-align:center;max-width:420px">
  <div style="display:flex;justify-content:center;margin-bottom:22px;color:var(--gold-d)"><?= logo() ?></div>
  <h2><?= e($title ?? 'Not found') ?></h2>
  <p class="muted" style="margin:10px 0 22px"><?= e($msg ?? '') ?></p>
  <a class="btn" href="<?php $site='https://anantceramic.com'; try { $site = setting('company_website', $site) ?: $site; } catch (Throwable $t) {} echo e($site); ?>">Visit anantceramic.com</a>
</div></body></html>
