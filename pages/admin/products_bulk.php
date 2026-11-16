<?php
require_admin();
if (!is_post()) redirect('admin/products');
csrf_check();
$rowsIn = json_decode((string)input('rows', '[]'), true);
if (!is_array($rowsIn) || !$rowsIn) json_out(['ok' => false, 'error' => 'No rows found in the file']);

$cats = rows('SELECT * FROM categories');
$findCat = function (string $s) use (&$cats) {
    $s = strtolower(trim(preg_replace('/[^a-z0-9 ]+/i', ' ', $s)));
    if ($s === '') return null;
    foreach ($cats as $c) if ($s === strtolower($c['grp'] . ' ' . $c['name']) || $s === strtolower($c['name'])) return $c;
    foreach ($cats as $c) if (str_contains($s, strtolower($c['name']))) return $c;
    // unknown sub-category → create it under the right group
    $grp = str_contains($s, 'sanit') ? 'sanitary' : (str_contains($s, 'fitt') ? 'fittings' : 'tiles');
    $name = ucwords(trim(str_ireplace(['tiles', 'sanitary', 'fittings'], '', $s))) ?: ucfirst($grp);
    $prefix = $grp === 'tiles' ? 'AC-1' : ($grp === 'sanitary' ? 'AC-2' : 'AC-3');
    $id = insert('categories', ['grp' => $grp, 'name' => $name, 'unit' => $grp === 'tiles' ? 'sqft' : 'pc', 'code_prefix' => $prefix]);
    $c = ['id' => $id, 'grp' => $grp, 'name' => $name, 'unit' => $grp === 'tiles' ? 'sqft' : 'pc', 'code_prefix' => $prefix];
    $cats[] = $c;
    return $c;
};
$norm = function (array $r): array {
    $o = [];
    foreach ($r as $k => $v) $o[strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', (string)$k), '_'))] = is_string($v) ? trim($v) : $v;
    return $o;
};
$numv = fn($v) => ($v === '' || $v === null) ? null : (float)preg_replace('/[^0-9.]/', '', (string)$v);

$created = 0; $updated = 0; $errors = [];
db()->beginTransaction();
foreach ($rowsIn as $i => $raw) {
    $r = $norm((array)$raw);
    $line = $i + 2;
    $name = $r['name'] ?? $r['product_name'] ?? $r['product'] ?? '';
    if ($name === '') { if (array_filter($r)) $errors[] = "Row $line: product name missing"; continue; }
    $cat = $findCat((string)($r['category'] ?? 'Tiles GVT'));
    $data = [
        'name' => $name,
        'category_id' => $cat['id'] ?? null,
        'size' => $r['size'] ?? null, 'finish' => $r['finish'] ?? null, 'thickness' => $r['thickness'] ?? null,
        'pcs_per_box' => $numv($r['pcs_per_box'] ?? $r['pieces_per_box'] ?? null),
        'coverage_per_box' => $numv($r['coverage_per_box'] ?? $r['coverage_sqft'] ?? null),
        'where_to_use' => $r['where_to_use'] ?? null,
        'unit' => $cat['unit'] ?? 'sqft',
        'mrp' => $numv($r['mrp'] ?? null), 'offer_price' => $numv($r['offer_price'] ?? $r['offer'] ?? null),
        'dealer_price' => $numv($r['dealer_price'] ?? $r['dealer'] ?? null), 'min_price' => $numv($r['min_price'] ?? $r['minimum_price'] ?? null),
        'purchase_cost' => $numv($r['purchase_cost'] ?? $r['cost'] ?? null), 'gst' => $numv($r['gst'] ?? null) ?? 18,
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    if (!empty($r['features'])) $data['features'] = json_encode(array_values(array_filter(array_map('trim', explode(',', (string)$r['features'])))));
    $data = array_filter($data, fn($v) => $v !== null && $v !== '');
    $code = strtoupper(trim((string)($r['code'] ?? $r['product_code'] ?? '')));
    try {
        $existing = $code !== '' ? row('SELECT id FROM products WHERE code = ?', [$code]) : null;
        if ($existing) { update('products', $data, 'id = ?', [$existing['id']]); $pid = (int)$existing['id']; $updated++; }
        else {
            if ($code === '') $code = next_product_code((int)$data['category_id']);
            $pid = insert('products', $data + ['code' => $code, 'code_key' => code_key($code), 'qr_status' => 'generated']);
            $created++;
        }
        $qty = $r['stock_qty'] ?? $r['stock'] ?? $r['boxes'] ?? '';
        if ($qty !== '' && $qty !== null) {
            $loc = (string)($r['stock_location'] ?? $r['location'] ?? 'Godown A') ?: 'Godown A';
            $ex = row('SELECT id FROM product_stock WHERE product_id = ? AND location = ? AND incoming = 0 LIMIT 1', [$pid, $loc]);
            $st = ['qty' => (int)$numv($qty), 'rack' => $r['rack'] ?? null, 'batch' => $r['batch'] ?? $r['batch_shade'] ?? null];
            if ($ex) update('product_stock', $st, 'id = ?', [$ex['id']]);
            else insert('product_stock', $st + ['product_id' => $pid, 'location' => $loc]);
        }
    } catch (PDOException $e) {
        $errors[] = "Row $line: " . ($e->getCode() === '23000' ? 'duplicate code' : 'could not save');
    }
}
db()->commit();
json_out(['ok' => true, 'created' => $created, 'updated' => $updated, 'errors' => $errors]);
