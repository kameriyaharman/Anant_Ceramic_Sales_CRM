<?php
require_admin();
$cols = ['code', 'name', 'category', 'size', 'finish', 'thickness', 'pcs_per_box', 'coverage_per_box', 'where_to_use', 'features',
    'mrp', 'offer_price', 'dealer_price', 'min_price', 'purchase_cost', 'gst', 'stock_location', 'rack', 'batch', 'stock_qty', 'qr_link'];
$template = input('template') === '1';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . ($template ? 'anant-products-template' : 'anant-products-' . date('Y-m-d')) . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, $template ? array_slice($cols, 0, -1) : $cols);
if ($template) {
    fputcsv($out, ['', 'Calacatta Gold Glossy', 'Tiles GVT', '600×1200 mm', 'Glossy', '9 mm', 2, 15.5, 'Floor · Wall · Living, bedroom', 'Stain resistant, Easy to clean', 112, 98, 86, 90, 72, 18, 'Godown A', 'R-12', 'B-24 · Shade 3', 220]);
    fputcsv($out, ['', 'Designer Table-top Basin', 'Sanitary Basin', '600×400 mm', 'Matt black', '', '', '', 'Bathroom', '', 7900, 6450, 5800, 5600, 4400, 18, 'Showroom', 'S-2', '', 14]);
    exit;
}
$list = rows('SELECT p.*, c.grp, c.name cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.active = 1 ORDER BY p.code');
foreach ($list as $p) {
    $stock = product_stock((int)$p['id']);
    $first = $stock[0] ?? [];
    fputcsv($out, [$p['code'], $p['name'], ucfirst((string)$p['grp']) . ' ' . $p['cat_name'], $p['size'], $p['finish'], $p['thickness'], $p['pcs_per_box'],
        $p['coverage_per_box'], $p['where_to_use'], implode(', ', product_features($p)), $p['mrp'], $p['offer_price'], $p['dealer_price'], $p['min_price'],
        $p['purchase_cost'], $p['gst'], $first['location'] ?? '', $first['rack'] ?? '', $first['batch'] ?? '', stock_total((int)$p['id']), qr_url($p)]);
}
