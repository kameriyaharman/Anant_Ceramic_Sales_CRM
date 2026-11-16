<?php
/**
 * Anant Ceramic Sales CRM — configuration
 *
 * BigRock / cPanel: copy config.local.sample.php to config.local.php and fill
 * in the MySQL database you created in cPanel. Nothing else needs editing.
 *
 * Railway / Docker: values are read from environment variables
 * (DB_HOST/DB_NAME/DB_USER/DB_PASS or Railway's MYSQLHOST/MYSQLDATABASE/...).
 */

$env = function (array $keys, $default = null) {
    foreach ($keys as $k) {
        $v = getenv($k);
        if ($v !== false && $v !== '') return $v;
    }
    return $default;
};

$config = [
    'db_host'    => $env(['DB_HOST', 'MYSQLHOST'], 'localhost'),
    'db_port'    => (int)$env(['DB_PORT', 'MYSQLPORT'], 3306),
    'db_name'    => $env(['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE'], 'anant_crm'),
    'db_user'    => $env(['DB_USER', 'MYSQLUSER'], 'root'),
    'db_pass'    => $env(['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_ROOT_PASSWORD'], ''),
    // First admin login created on install (change it from Settings after logging in)
    'admin_email'    => $env(['ADMIN_EMAIL'], 'admin@anantceramic.com'),
    'admin_password' => $env(['ADMIN_PASSWORD'], 'Anant@123'),
    // Load demo products / staff / quotations on first install
    'seed_demo'  => $env(['SEED_DEMO'], '1') === '1',
    'timezone'   => 'Asia/Kolkata',
    'debug'      => $env(['APP_DEBUG'], '0') === '1',
];

if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    if (is_array($local)) $config = array_merge($config, $local);
}

return $config;
