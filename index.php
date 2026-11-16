<?php
/**
 * Anant Ceramic Sales CRM — front controller.
 * Every request (except real files in /assets and /uploads) is routed here by .htaccess.
 */
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) return false; // local dev server
require __DIR__ . '/core/bootstrap.php';
require __DIR__ . '/core/install.php';

try {
    ensure_installed();
} catch (PDOException $e) {
    http_response_code(500);
    $msg = $GLOBALS['CFG']['debug'] ? $e->getMessage() : 'Could not connect to the database. Check config.local.php (DB host, name, user, password).';
    view('public/error', ['msg' => $msg, 'title' => 'Setup needed']);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (BASE !== '' && str_starts_with($path, BASE)) $path = substr($path, strlen(BASE));
$path = trim(rawurldecode($path), '/');
if ($path === 'index.php') $path = '';

$routes = [
    ''                                  => fn() => redirect(staff_user() && !admin() ? 'app' : 'admin'),
    // admin
    'admin/login'                       => 'admin/login',
    'admin/logout'                      => 'admin/logout',
    'admin'                             => 'admin/dashboard',
    'admin/products'                    => 'admin/products',
    'admin/products/new'                => 'admin/product_edit',
    'admin/products/(\d+)'              => 'admin/product_edit',
    'admin/products/bulk'               => 'admin/products_bulk',
    'admin/products/export'             => 'admin/products_export',
    'admin/visibility'                  => 'admin/visibility',
    'admin/qr'                          => 'admin/qr',
    'admin/staff'                       => 'admin/staff',
    'admin/quotations'                  => 'admin/quotations',
    'admin/quotations/(\d+)'            => 'admin/quotation_view',
    'admin/customers'                   => 'admin/customers',
    'admin/reports'                     => 'admin/reports',
    'admin/settings'                    => 'admin/settings',
    'admin/search'                      => 'admin/search',
    // staff app
    'app/login'                         => 'staff/login',
    'app/logout'                        => 'staff/logout',
    'app'                               => 'staff/home',
    'app/scan'                          => 'staff/scan',
    'app/search'                        => 'staff/search',
    'app/p/([A-Za-z0-9-]+)'             => 'staff/product',
    'app/p/([A-Za-z0-9-]+)/details'     => 'staff/product_details',
    'app/p/([A-Za-z0-9-]+)/add'         => 'staff/add_item',
    'app/p/([A-Za-z0-9-]+)/reprint'     => 'staff/reprint',
    'app/quote'                         => 'staff/cart',
    'app/quote/customer'                => 'staff/customer',
    'app/quotes'                        => 'staff/quotes',
    'app/quotes/(\d+)'                  => 'staff/quote_view',
    'app/me'                            => 'staff/me',
    // customer (public, no login)
    'q/([A-Za-z0-9-]+)'                 => 'public/product',
    'q/([A-Za-z0-9-]+)/details'         => 'public/product_details',
    'q/([A-Za-z0-9-]+)/request'         => 'public/request',
    'q/([A-Za-z0-9-]+)/thanks'          => 'public/thanks',
    'q/([A-Za-z0-9-]+)/go/(whatsapp|call)' => 'public/go',
    // quotation document (shared on WhatsApp)
    'quotation/([a-f0-9]{16,40})'       => 'public/quotation',
    'health'                            => fn() => json_out(['ok' => true]),
];

foreach ($routes as $pattern => $handler) {
    if (preg_match('#^' . $pattern . '$#', $path, $m)) {
        array_shift($m);
        $params = $m;
        if (is_callable($handler)) { $handler(...$params); exit; }
        require __DIR__ . '/pages/' . $handler . '.php';
        exit;
    }
}
not_found();
