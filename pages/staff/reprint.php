<?php
require_staff();
$p = product_by_code($params[0]);
if (!$p || !is_post()) redirect('app');
csrf_check();
update('products', ['reprint_requested' => 1], 'id = ?', [$p['id']]);
flash('Reprint requested — admin will print a new QR label.');
redirect('app/p/' . $p['code']);
