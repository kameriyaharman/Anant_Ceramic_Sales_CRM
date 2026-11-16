<?php
/** Creates tables on first run and (optionally) loads demo data. */

const SCHEMA_VERSION = '1';

function ensure_installed(): void {
    try {
        $v = val("SELECT v FROM settings WHERE k = 'schema_version'");
        if ($v === SCHEMA_VERSION) return;
    } catch (PDOException $e) {
        if (!str_contains($e->getMessage(), 'settings') && $e->getCode() !== '42S02') throw $e;
    }
    run_install();
}

function run_install(): void {
    $sql = file_get_contents(ROOT . '/core/schema.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) db()->exec($stmt);

    $cfg = $GLOBALS['CFG'];
    if (!val('SELECT COUNT(*) FROM admins')) {
        insert('admins', ['name' => 'Admin', 'email' => $cfg['admin_email'], 'password_hash' => password_hash($cfg['admin_password'], PASSWORD_DEFAULT)]);
    }
    $defaults = [
        'company_name' => 'Anant Ceramic',
        'company_address' => 'Chikhli Char Rasta, Dist. Navsari, Gujarat',
        'company_phone' => '+91 99136 61100',
        'company_whatsapp' => '919913661100',
        'company_website' => 'https://anantceramic.com',
        'company_gstin' => '',
        'showroom' => 'Chikhli Char Rasta, Navsari',
        'qr_base_url' => '',
        'quote_prefix' => 'QT',
        'quote_validity' => '7',
        'quote_terms' => 'Prices per admin quotation settings · shade may vary batch to batch · transport & unloading extra · quotation valid 7 days.',
        'prices_incl_gst' => '0',
        'low_stock_boxes' => '40',
        'wastage_pct' => '5',
    ];
    foreach ($defaults as $k => $v) q('INSERT IGNORE INTO settings (k, v) VALUES (?, ?)', [$k, $v]);

    if (!val('SELECT COUNT(*) FROM categories')) {
        foreach ([
            ['tiles', 'GVT', 'sqft', 'AC-1'], ['tiles', 'PGVT', 'sqft', 'AC-1'], ['tiles', 'Wooden', 'sqft', 'AC-1'],
            ['tiles', 'Ceramic Wall', 'sqft', 'AC-1'], ['sanitary', 'Basin', 'pc', 'AC-21'], ['sanitary', 'WC', 'pc', 'AC-22'],
            ['fittings', 'Faucet', 'pc', 'AC-31'], ['fittings', 'Shower', 'pc', 'AC-32'],
        ] as [$g, $n, $u, $p]) insert('categories', ['grp' => $g, 'name' => $n, 'unit' => $u, 'code_prefix' => $p]);
    }
    if (!val('SELECT COUNT(*) FROM roles')) {
        $all = ['scan' => 1, 'dealer' => 1, 'min' => 1, 'location' => 1, 'quote' => 1, 'below_min' => 0, 'others' => 0];
        insert('roles', ['name' => 'Sales executive', 'max_discount' => 5, 'perms' => json_encode($all)]);
        insert('roles', ['name' => 'Senior sales', 'max_discount' => 8, 'perms' => json_encode(['others' => 1] + $all)]);
        insert('roles', ['name' => 'Godown', 'max_discount' => 0, 'perms' => json_encode(['quote' => 0, 'dealer' => 0, 'min' => 0] + $all)]);
        insert('roles', ['name' => 'Sales trainee', 'max_discount' => 2, 'perms' => json_encode(['dealer' => 0, 'min' => 0] + $all)]);
    }
    if ($cfg['seed_demo'] && !val('SELECT COUNT(*) FROM products')) {
        require_once ROOT . '/core/seed.php';
        seed_demo();
    }
    set_setting('schema_version', SCHEMA_VERSION);
}
