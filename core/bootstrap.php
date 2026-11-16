<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$GLOBALS['CFG'] = require ROOT . '/config.php';
date_default_timezone_set($GLOBALS['CFG']['timezone']);

if ($GLOBALS['CFG']['debug']) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
else { ini_set('display_errors', '0'); }

// ---------- session ----------
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('ANANTCRM');
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $secure]);
session_start();

// ---------- base url (works in a sub-folder on shared hosting) ----------
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$scriptDir = (PHP_SAPI === 'cli-server' || !str_ends_with($scriptName, '.php')) ? '' : rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
define('BASE', $scriptDir === '/' ? '' : $scriptDir);

function url(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }
function abs_url(string $path = ''): string {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $proto . '://' . $host . url($path);
}
function asset(string $p): string {
    $f = ROOT . '/assets/' . $p;
    return url('assets/' . $p) . (is_file($f) ? '?v=' . filemtime($f) : '');
}
function upload_url(?string $p): string { return $p ? url('uploads/' . $p) : ''; }

// ---------- db ----------
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $c = $GLOBALS['CFG'];
    $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+05:30'");
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement { $s = db()->prepare($sql); $s->execute($p); return $s; }
function row(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

function insert(string $table, array $data): int {
    $cols = array_keys($data);
    $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    q($sql, array_values($data));
    return (int)db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $wp = []): void {
    $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
    q("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $wp));
}

// ---------- settings ----------
function setting(string $k, $default = '') {
    static $cache = null;
    if ($cache === null) { $cache = []; foreach (rows('SELECT k, v FROM settings') as $r) $cache[$r['k']] = $r['v']; }
    return array_key_exists($k, $cache) ? $cache[$k] : $default;
}
function set_setting(string $k, $v): void {
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, (string)$v]);
}

// ---------- output helpers ----------
function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function inr($n, int $dec = 0): string {
    $n = round((float)$n, $dec); $neg = $n < 0;
    $parts = explode('.', number_format(abs($n), $dec, '.', ''));
    $whole = $parts[0]; $frac = isset($parts[1]) ? '.' . $parts[1] : '';
    if (strlen($whole) > 3) {
        $rest = substr($whole, 0, -3);
        $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . substr($whole, -3);
    }
    return ($neg ? '-' : '') . '₹' . $whole . $frac;
}
function money($n): string { // 2 decimals only when needed
    $n = round((float)$n, 2);
    return inr($n, fmod($n, 1.0) == 0.0 ? 0 : 2);
}
function lakh($n): string {
    $n = (float)$n;
    if ($n >= 10000000) return '₹' . rtrim(rtrim(number_format($n / 10000000, 2), '0'), '.') . ' Cr';
    if ($n >= 100000) return '₹' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . ' L';
    return inr($n);
}
function num($n): string { $n = (float)$n; return fmod($n, 1.0) == 0.0 ? number_format($n) : rtrim(rtrim(number_format($n, 2), '0'), '.'); }
function unit_label(string $u): string { return $u === 'pc' ? 'pc' : 'sq.ft'; }
function nice_date(?string $d, bool $time = false): string {
    if (!$d) return '—';
    $t = strtotime($d);
    if (date('Y-m-d', $t) === date('Y-m-d')) return $time ? date('g:i A', $t) : 'Today';
    if (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date($time ? 'j M · g:i A' : 'j M', $t);
}
function initials(string $n): string {
    return mb_strtoupper(mb_substr(trim($n), 0, 2));
}
function mask_mobile(?string $m): string { $m = (string)$m; return strlen($m) >= 10 ? '+91 ' . substr($m, -10, 5) . ' ' . substr($m, -5) : $m; }
function clean_mobile(string $m): string { $d = preg_replace('/\D/', '', $m); return strlen($d) > 10 ? substr($d, -10) : $d; }
function code_key(string $code): string { return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code)); }

// ---------- request helpers ----------
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function input(string $k, $d = '') { return $_POST[$k] ?? $_GET[$k] ?? $d; }
function redirect(string $path): never { header('Location: ' . (preg_match('#^(https?://|tel:|mailto:)#', $path) ? $path : url($path))); exit; }
function json_out($data, int $code = 200): never { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }
function flash(string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    if (!hash_equals(csrf_token(), (string)$t)) { http_response_code(419); exit('Session expired — please go back and try again.'); }
}

// ---------- auth ----------
function admin(): ?array {
    static $a = false;
    if ($a === false) $a = !empty($_SESSION['admin_id']) ? row('SELECT * FROM admins WHERE id = ?', [$_SESSION['admin_id']]) : null;
    return $a;
}
function require_admin(): array { $a = admin(); if (!$a) redirect('admin/login'); return $a; }

function staff_user(): ?array {
    static $s = false;
    if ($s === false) {
        $s = null;
        if (!empty($_SESSION['staff_id'])) {
            $s = row('SELECT s.*, r.name role_name, r.max_discount, r.perms FROM staff s LEFT JOIN roles r ON r.id = s.role_id WHERE s.id = ? AND s.status = "active"', [$_SESSION['staff_id']]);
            if ($s) $s['perm'] = array_merge(default_perms(), json_decode($s['perms'] ?: '{}', true) ?: []);
        }
    }
    return $s;
}
function require_staff(): array { $s = staff_user(); if (!$s) redirect('app/login'); return $s; }

function default_perms(): array {
    return ['scan' => 1, 'dealer' => 0, 'min' => 0, 'location' => 1, 'quote' => 1, 'below_min' => 0, 'others' => 0];
}
function perm_labels(): array {
    return [
        'scan' => 'Scan products & see staff view',
        'dealer' => 'See dealer price',
        'min' => 'See minimum selling price',
        'location' => 'See stock & godown location',
        'quote' => 'Create quotations',
        'below_min' => 'Edit price in quotation below minimum',
        'others' => 'See other staff’s quotations',
    ];
}

// ---------- scan visibility ----------
function visibility_fields(): array {
    return [
        'PRODUCT' => [
            'photos' => 'Photos & room images', 'name' => 'Product name', 'code' => 'Product code',
            'specs' => 'Category, size, thickness, finish', 'perbox' => 'Pieces & coverage per box',
            'features' => 'Features & where to use', 'video' => 'Laying / room video',
        ],
        'PRICE' => [
            'mrp' => 'MRP', 'offer' => 'Offer price', 'dealer' => 'Dealer / contractor price', 'min' => 'Minimum selling price',
        ],
        'STOCK' => [
            'stock_qty' => 'Stock available (boxes)', 'stock_label' => '“In stock / Limited stock” label only',
            'location' => 'Godown & rack location', 'batch' => 'Batch / shade number',
        ],
        'ACTIONS' => [
            'whatsapp' => 'WhatsApp / call enquiry button', 'request' => 'Request a quotation (callback form)',
            'similar' => 'Similar products', 'add_quote' => 'Add to quotation',
        ],
    ];
}
function default_visibility(): array {
    $c = ['photos'=>1,'name'=>1,'code'=>0,'specs'=>1,'perbox'=>1,'features'=>1,'video'=>1,'mrp'=>1,'offer'=>1,'dealer'=>0,'min'=>0,
          'stock_qty'=>0,'stock_label'=>1,'location'=>0,'batch'=>0,'whatsapp'=>1,'request'=>1,'similar'=>1,'add_quote'=>0];
    $s = ['photos'=>1,'name'=>1,'code'=>1,'specs'=>1,'perbox'=>1,'features'=>1,'video'=>1,'mrp'=>1,'offer'=>1,'dealer'=>1,'min'=>1,
          'stock_qty'=>1,'stock_label'=>0,'location'=>1,'batch'=>1,'whatsapp'=>0,'request'=>0,'similar'=>1,'add_quote'=>1];
    return ['customer' => $c, 'staff' => $s];
}
function visibility_for(array $product): array {
    $base = default_visibility();
    $grp = $product['grp'] ?? 'tiles';
    $t = val('SELECT data FROM visibility_templates WHERE grp = ?', [$grp]);
    if ($t) $base = array_replace_recursive($base, json_decode($t, true) ?: []);
    if (!empty($product['visibility'])) $base = array_replace_recursive($base, json_decode($product['visibility'], true) ?: []);
    return $base;
}

// ---------- products ----------
function product_by_code(string $code): ?array {
    return row('SELECT p.*, c.grp, c.name cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE (p.code = ? OR p.code_key = ?) AND p.active = 1', [$code, code_key($code)]);
}
function product_by_id(int $id): ?array {
    return row('SELECT p.*, c.grp, c.name cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?', [$id]);
}
function product_images(int $id): array { return rows('SELECT * FROM product_images WHERE product_id = ? ORDER BY kind, sort, id', [$id]); }
function product_stock(int $id): array { return rows('SELECT * FROM product_stock WHERE product_id = ? ORDER BY incoming, id', [$id]); }
function stock_total(int $id): int { return (int)val('SELECT COALESCE(SUM(qty),0) FROM product_stock WHERE product_id = ? AND incoming = 0', [$id]); }
function stock_unit(array $p): string { return $p['unit'] === 'pc' ? 'pc' : 'boxes'; }
function cat_label(array $p): string { return trim(ucfirst((string)($p['grp'] ?? '')) . ' · ' . ($p['cat_name'] ?? ''), ' ·'); }
function qr_url(array $p): string {
    $base = rtrim((string)setting('qr_base_url', ''), '/');
    if ($base === '') $base = rtrim(abs_url('q'), '/');
    return $base . '/' . $p['code_key'];
}
function product_thumb(array $p): string {
    $img = val('SELECT path FROM product_images WHERE product_id = ? AND kind = "photo" ORDER BY sort, id LIMIT 1', [$p['id']]);
    return $img ? upload_url($img) : url('assets/img/placeholder.svg');
}
function product_features(array $p): array { return json_decode($p['features'] ?: '[]', true) ?: []; }
function next_product_code(int $categoryId): string {
    $prefix = val('SELECT code_prefix FROM categories WHERE id = ?', [$categoryId]) ?: 'AC-1';
    $codes = rows('SELECT code FROM products WHERE code LIKE ?', [$prefix . '%']);
    $max = 0;
    foreach ($codes as $c) { $n = (int)substr($c['code'], strlen($prefix)); if ($n > $max) $max = $n; }
    $width = max(2, 7 - strlen($prefix));   // AC-1 + 001 = AC-1001, AC-21 + 01 = AC-2101
    return $prefix . str_pad((string)($max + 1), $width, '0', STR_PAD_LEFT);
}

function log_scan(int $productId, string $type, ?int $staffId = null): void {
    // ignore repeat scans of the same product by the same person within 2 minutes
    $key = "scan_{$type}_{$productId}";
    if (!empty($_SESSION[$key]) && time() - $_SESSION[$key] < 120) return;
    $_SESSION[$key] = time();
    insert('scans', ['product_id' => $productId, 'type' => $type, 'staff_id' => $staffId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ---------- quotations ----------
function next_quote_number(): string {
    $fmt = setting('quote_prefix', 'QT');
    $ym = date('ym');
    $prefix = "$fmt-$ym-";
    $n = 1 + (int)val('SELECT MAX(CAST(SUBSTRING(number, ?) AS UNSIGNED)) FROM quotations WHERE number LIKE ?', [strlen($prefix) + 1, $prefix . '%']);
    return $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
}
function recalc_quote(int $qid): void {
    $items = rows('SELECT * FROM quotation_items WHERE quotation_id = ?', [$qid]);
    $sub = 0; $gst = 0;
    foreach ($items as $i) { $sub += $i['amount']; $gst += $i['amount'] * $i['gst'] / 100; }
    $incl = setting('prices_incl_gst', '0') === '1';
    if ($incl) { $total = $sub; $gst = $sub - array_sum(array_map(fn($i) => $i['amount'] / (1 + $i['gst'] / 100), $items)); $sub = $total - $gst; }
    $total = round($sub + $gst);
    update('quotations', ['subtotal' => round($sub, 2), 'gst_amount' => round($gst, 2), 'total' => $total, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$qid]);
}
function status_badge(string $s): string {
    $map = ['draft' => ['Draft', 'grey'], 'approval' => ['Approval', 'blue'], 'ready' => ['Ready', 'gold'], 'sent' => ['Sent', 'gold'],
        'won' => ['Won', 'green'], 'lost' => ['Lost', 'red'], 'new' => ['New', 'blue'], 'contacted' => ['Contacted', 'gold'],
        'converted' => ['Converted', 'green'], 'closed' => ['Closed', 'grey'], 'pending' => ['Needs approval', 'blue'],
        'approved' => ['Approved', 'green'], 'rejected' => ['Rejected', 'red'], 'countered' => ['Countered', 'gold']];
    [$l, $c] = $map[$s] ?? [ucfirst($s), 'grey'];
    return "<span class=\"badge b-$c\">" . e($l) . "</span>";
}
function quote_items(int $qid): array {
    return rows('SELECT i.*, p.name, p.code, p.size, p.finish, p.unit, p.coverage_per_box, p.pcs_per_box, p.offer_price, p.min_price FROM quotation_items i JOIN products p ON p.id = i.product_id WHERE i.quotation_id = ? ORDER BY i.sort, i.id', [$qid]);
}
function item_qty_label(array $i): string {
    if ($i['unit'] === 'pc') return num($i['boxes']) . ' pcs';
    return num($i['boxes']) . ' boxes · ' . num($i['area']) . ' sq.ft';
}

// ---------- uploads ----------
function save_upload(array $f, string $dir = 'products'): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) return null;
    if (@getimagesize($f['tmp_name']) === false) return null;
    $rel = $dir . '/' . date('Ym') . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
    $abs = ROOT . '/uploads/' . $rel;
    if (!is_dir(dirname($abs))) mkdir(dirname($abs), 0775, true);
    return move_uploaded_file($f['tmp_name'], $abs) ? $rel : null;
}

require_once __DIR__ . '/ui.php';

// ---------- views ----------
function view(string $name, array $vars = []): void {
    extract($vars);
    require ROOT . '/views/' . $name . '.php';
}
function not_found(string $msg = 'Page not found'): never {
    http_response_code(404);
    view('public/error', ['msg' => $msg]);
    exit;
}

/** After an approval decision, move the quotation to the right status. */
function refresh_quote_status(int $qid): void {
    $q = row('SELECT status FROM quotations WHERE id = ?', [$qid]);
    if (!$q || !in_array($q['status'], ['approval', 'draft', 'ready'], true)) return;
    $c = row('SELECT SUM(approval IN ("pending","countered")) open, SUM(approval = "rejected") rej FROM quotation_items WHERE quotation_id = ?', [$qid]);
    if ((int)$c['open'] > 0) $s = 'approval';
    elseif ((int)$c['rej'] > 0) $s = 'draft';
    else $s = $q['status'] === 'draft' ? 'draft' : 'ready';
    update('quotations', ['status' => $s, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$qid]);
}

/** Admin decision on one quotation item: approve | reject | counter */
function decide_item(int $itemId, string $decision, ?float $counter = null): ?int {
    $i = row('SELECT * FROM quotation_items WHERE id = ?', [$itemId]);
    if (!$i) return null;
    $now = date('Y-m-d H:i:s');
    if ($decision === 'approve') update('quotation_items', ['approval' => 'approved', 'approval_at' => $now], 'id = ?', [$itemId]);
    elseif ($decision === 'reject') update('quotation_items', ['approval' => 'rejected', 'approval_at' => $now], 'id = ?', [$itemId]);
    elseif ($decision === 'counter' && $counter > 0) update('quotation_items', ['approval' => 'countered', 'counter_rate' => $counter, 'approval_at' => $now], 'id = ?', [$itemId]);
    refresh_quote_status((int)$i['quotation_id']);
    return (int)$i['quotation_id'];
}

function quote_full(int $id): ?array {
    return row('SELECT q.*, c.name cname, c.mobile cmobile, c.address caddress, c.city ccity, st.name sname, st.mobile smobile
        FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id LEFT JOIN staff st ON st.id = q.staff_id WHERE q.id = ?', [$id]);
}
function quote_share_text(array $q): string {
    return 'Hello ' . ($q['cname'] ?: '') . ', here is your quotation ' . $q['number'] . ' from ' . setting('company_name', 'Anant Ceramic')
        . ' — Grand total ' . inr($q['total']) . ".\n" . abs_url('quotation/' . $q['share_token']);
}

// ---------- staff quotation helpers ----------
function current_draft(array $staff): ?array {
    $id = (int)($_SESSION['draft_id'] ?? 0);
    $q = $id ? row('SELECT * FROM quotations WHERE id = ? AND staff_id = ? AND status = "draft"', [$id, $staff['id']]) : null;
    if (!$q) unset($_SESSION['draft_id']);
    return $q;
}
function ensure_draft(array $staff): array {
    if ($q = current_draft($staff)) return $q;
    $id = insert('quotations', ['number' => next_quote_number(), 'share_token' => bin2hex(random_bytes(12)), 'staff_id' => $staff['id'], 'status' => 'draft',
        'valid_till' => date('Y-m-d', strtotime('+' . (int)setting('quote_validity', '7') . ' days'))]);
    $_SESSION['draft_id'] = $id;
    return row('SELECT * FROM quotations WHERE id = ?', [$id]);
}
function can_see_quote(array $staff, array $q): bool { return (int)$q['staff_id'] === (int)$staff['id'] || !empty($staff['perm']['others']); }
function discount_pct(float $offer, float $rate): float { return $offer > 0 ? round(($offer - $rate) / $offer * 100, 2) : 0.0; }
/** Returns '' when the staff can give this rate on their own, else a short reason. */
function approval_reason_for(array $staff, array $product, float $rate): string {
    $disc = discount_pct((float)$product['offer_price'], $rate);
    $max = (float)($staff['max_discount'] ?? 0);
    $r = [];
    if ($disc > $max + 0.001) $r[] = 'Discount ' . num($disc) . '% (limit ' . num($max) . '%)';
    if ($product['min_price'] !== null && $rate < (float)$product['min_price'] - 0.001 && empty($staff['perm']['below_min'])) $r[] = 'Below minimum ' . money($product['min_price']);
    return implode(' · ', $r);
}
