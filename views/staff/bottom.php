<?php
$nav = $nav ?? '';
$draftN = 0;
if (staff_user() && !empty($_SESSION['draft_id'])) $draftN = (int)val('SELECT COUNT(*) FROM quotation_items i JOIN quotations q ON q.id = i.quotation_id WHERE q.id = ? AND q.status = "draft"', [$_SESSION['draft_id']]);
?>
<?php if (empty($noNav)): ?>
<nav class="anav">
  <a href="<?= url('app') ?>" class="<?= $nav === 'home' ? 'on' : '' ?>"><?= icon('home') ?>Home</a>
  <a href="<?= url('app/search') ?>" class="<?= $nav === 'products' ? 'on' : '' ?>"><?= icon('box') ?>Products</a>
  <a href="<?= url('app/scan') ?>" class="scan"><span><?= icon('scan') ?></span>Scan</a>
  <a href="<?= url($draftN ? 'app/quote' : 'app/quotes') ?>" class="<?= $nav === 'quotes' ? 'on' : '' ?>"><?= icon('doc') ?>Quotes<?php if ($draftN): ?><i class="dot"><?= $draftN ?></i><?php endif ?></a>
  <a href="<?= url('app/me') ?>" class="<?= $nav === 'me' ? 'on' : '' ?>"><?= icon('user') ?>Me</a>
</nav>
<?php endif ?>
</div>
<script>window.CSRF=<?= json_encode(csrf_token()) ?>;window.BASE=<?= json_encode(BASE) ?>;
if('serviceWorker' in navigator)navigator.serviceWorker.register(<?= json_encode(url('sw.js')) ?>).catch(()=>{});</script>
<?= $foot ?? '' ?>
</body></html>
