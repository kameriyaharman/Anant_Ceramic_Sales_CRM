<?php
/** Customer tapped WhatsApp / Call on a product page — count it as an enquiry, then open the app. */
$p = product_by_code($params[0]);
if (!$p) not_found();
$channel = $params[1];
$key = "go_{$channel}_{$p['id']}";
if (empty($_SESSION[$key])) {
    $_SESSION[$key] = 1;
    insert('leads', ['customer_id' => 0, 'product_id' => $p['id'], 'channel' => $channel, 'status' => 'logged', 'note' => 'Tapped ' . $channel . ' on product page']);
}
if ($channel === 'call') redirect('tel:' . preg_replace('/[^\d+]/', '', setting('company_phone')));
redirect('https://wa.me/' . preg_replace('/\D/', '', setting('company_whatsapp')) . '?text=' . rawurlencode("Hi Anant Ceramic, I'm interested in {$p['name']} ({$p['code']}).\n" . qr_url($p)));
