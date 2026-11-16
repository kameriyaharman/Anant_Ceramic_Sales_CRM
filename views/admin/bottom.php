  </main>
</div>
<script>window.CSRF = <?= json_encode(csrf_token()) ?>; window.BASE = <?= json_encode(BASE) ?>;</script>
<script src="<?= asset('js/admin.js') ?>"></script>
<?= $foot ?? '' ?>
</body></html>
