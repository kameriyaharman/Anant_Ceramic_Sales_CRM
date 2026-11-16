<?php
require_admin();
if (is_post()) {
    csrf_check();
    if (input('action') === 'lead') {
        $s = (string)input('status');
        if (in_array($s, ['new', 'contacted', 'converted', 'closed'], true)) update('leads', ['status' => $s], 'id = ?', [(int)input('lead')]);
    }
    if (input('action') === 'customer') {
        update('customers', ['name' => trim((string)input('name')) ?: null, 'city' => trim((string)input('city')) ?: null, 'notes' => trim((string)input('notes')) ?: null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [(int)input('id')]);
        flash('Customer saved.');
    }
    redirect('admin/customers' . (input('back') ? '?' . input('back') : ''));
}
$tab = (string)input('tab', '');
$qs = trim((string)input('q', ''));
$where = ['1=1']; $par = [];
if ($tab === 'leads') $where[] = 'c.source LIKE "qr_%"';
if ($tab === 'customers') $where[] = 'c.source = "quotation"';
if ($tab === 'new') $where[] = 'EXISTS (SELECT 1 FROM leads l WHERE l.customer_id = c.id AND l.status = "new")';
if ($qs !== '') { $where[] = '(c.name LIKE ? OR c.mobile LIKE ? OR c.city LIKE ?)'; array_push($par, "%$qs%", "%$qs%", "%$qs%"); }
$list = rows('SELECT c.*,
   (SELECT l.id FROM leads l WHERE l.customer_id = c.id ORDER BY l.created_at DESC LIMIT 1) lead_id,
   (SELECT q.id FROM quotations q WHERE q.customer_id = c.id ORDER BY q.created_at DESC LIMIT 1) quote_id,
   GREATEST(c.updated_at, COALESCE((SELECT MAX(l.created_at) FROM leads l WHERE l.customer_id = c.id), c.created_at), COALESCE((SELECT MAX(q.updated_at) FROM quotations q WHERE q.customer_id = c.id), c.created_at)) last_at
   FROM customers c WHERE ' . implode(' AND ', $where) . ' ORDER BY last_at DESC LIMIT 300', $par);
$sel = input('id') ? row('SELECT * FROM customers WHERE id = ?', [(int)input('id')]) : null;

$wk = date('Y-m-d', strtotime('-6 days'));
$scanTop = rows('SELECT p.name, COUNT(*) n FROM scans s JOIN products p ON p.id = s.product_id WHERE s.type = "customer" AND s.created_at >= ? GROUP BY p.id ORDER BY n DESC LIMIT 6', [$wk]);
$maxS = max(1, ...array_map(fn($r) => (int)$r['n'], $scanTop ?: [['n' => 1]]));
$custScans = (int)val('SELECT COUNT(*) FROM scans WHERE type = "customer" AND created_at >= ?', [$wk]);
$contacts = (int)val('SELECT COUNT(*) FROM leads WHERE channel IN ("whatsapp","call") AND created_at >= ?', [$wk]);
$requests = (int)val('SELECT COUNT(*) FROM leads WHERE channel IN ("callback","visit") AND created_at >= ?', [$wk]);
$staffScans = (int)val('SELECT COUNT(*) FROM scans WHERE type = "staff" AND created_at >= ?', [$wk]);
$staffQuotes = (int)val('SELECT COUNT(*) FROM quotations WHERE status <> "draft" AND created_at >= ?', [$wk]);
$srcLabel = ['quotation' => 'Staff quotation', 'qr_whatsapp' => 'QR scan → WhatsApp', 'qr_callback' => 'QR scan → callback', 'qr_call' => 'QR scan → call', 'qr_visit' => 'QR scan → showroom visit'];

$title = 'Customers, leads & scan reports'; $active = 'customers';
require ROOT . '/views/admin/top.php';
?>
<div class="grid" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <div class="card">
    <div class="hd"><div class="tabs">
      <?php foreach (['' => 'All', 'new' => 'New leads', 'leads' => 'From QR scans', 'customers' => 'From quotations'] as $k => $l): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= url('admin/customers?tab=' . $k) ?>"><?= $l ?></a><?php endforeach ?>
    </div><form><input type="hidden" name="tab" value="<?= e($tab) ?>"><input type="search" name="q" value="<?= e($qs) ?>" placeholder="Name, mobile, city" style="padding:6px 10px;width:170px"></form></div>
    <div class="tbl-wrap"><table class="t">
      <thead><tr><th>Customer / lead</th><th>Source</th><th>Interested in</th><th>Last activity</th></tr></thead>
      <tbody><?php foreach ($list as $c):
        $lead = $c['lead_id'] ? row('SELECT l.*, p.name pname FROM leads l LEFT JOIN products p ON p.id = l.product_id WHERE l.id = ?', [$c['lead_id']]) : null;
        $qt = $c['quote_id'] ? row('SELECT q.*, (SELECT COUNT(*) FROM quotation_items i WHERE i.quotation_id = q.id) items FROM quotations q WHERE q.id = ?', [$c['quote_id']]) : null;
        $useQuote = $qt && (!$lead || $qt['updated_at'] >= $lead['created_at']); ?>
        <tr class="click" onclick="location='<?= url('admin/customers?id=' . $c['id'] . '&tab=' . $tab) ?>'">
          <td><div class="b"><?= e($c['name'] ?: 'Unknown (customer scan)') ?></div><div class="xs muted"><?= e(mask_mobile($c['mobile'])) ?><?= $c['city'] ? ' · ' . e($c['city']) : '' ?></div></td>
          <td class="small"><?= e($useQuote ? 'Staff quotation' : ($lead ? ($srcLabel['qr_' . $lead['channel']] ?? $lead['channel']) : ($srcLabel[$c['source']] ?? $c['source']))) ?></td>
          <td class="small"><?php if ($useQuote): ?><?= $qt['items'] ?> items · <?= inr($qt['total']) ?><?php elseif ($lead): ?><?= e($lead['pname'] ?: '—') ?><?= $lead['area'] ? ' · ' . e($lead['area']) : '' ?><?php else: ?>—<?php endif ?></td>
          <td class="small"><?php if ($useQuote): ?><?= status_badge($qt['status']) ?> <span class="xs muted"><?= nice_date($qt['updated_at']) ?></span>
            <?php elseif ($lead): ?><?= status_badge($lead['status']) ?> <span class="xs muted"><?= $lead['callback_at'] ? 'Call back ' . date('g A', strtotime($lead['callback_at'])) : nice_date($lead['created_at']) ?></span><?php endif ?></td>
        </tr>
      <?php endforeach ?></tbody>
    </table></div>
    <?php if (!$list): ?><div class="empty">No customers yet.</div><?php endif ?>
  </div>

  <div class="stack">
    <?php if ($sel):
      $leads = rows('SELECT l.*, p.name pname, p.code FROM leads l LEFT JOIN products p ON p.id = l.product_id WHERE l.customer_id = ? ORDER BY l.created_at DESC', [$sel['id']]);
      $qts = rows('SELECT * FROM quotations WHERE customer_id = ? ORDER BY created_at DESC', [$sel['id']]); ?>
    <div class="card pad">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="customer"><input type="hidden" name="id" value="<?= $sel['id'] ?>"><input type="hidden" name="back" value="<?= e('id=' . $sel['id']) ?>">
        <div class="row sb" style="margin-bottom:12px"><h3><?= e($sel['name'] ?: 'Unknown customer') ?></h3>
          <div class="row"><a class="btn sm" href="tel:+91<?= e($sel['mobile']) ?>"><?= icon('phone') ?></a><a class="btn sm wa" target="_blank" href="<?= e(wa_link($sel['mobile'], 'Hello from ' . setting('company_name', 'Anant Ceramic') . '!')) ?>"><?= icon('wa') ?></a></div></div>
        <div class="fgrid" style="grid-template-columns:1fr 1fr"><div class="field"><label class="f">Name</label><input name="name" value="<?= e($sel['name']) ?>"></div><div class="field"><label class="f">City</label><input name="city" value="<?= e($sel['city']) ?>"></div></div>
        <div class="field"><label class="f">Mobile</label><input value="+91 <?= e($sel['mobile']) ?>" disabled></div>
        <div class="field"><label class="f">Notes</label><textarea name="notes"><?= e($sel['notes']) ?></textarea></div>
        <button class="btn dark sm">Save</button>
      </form>
      <?php foreach ($leads as $l): ?>
        <form method="post" class="row sb" style="padding:10px 0;border-top:1px solid #f2eee7"><?= csrf_field() ?><input type="hidden" name="action" value="lead"><input type="hidden" name="lead" value="<?= $l['id'] ?>"><input type="hidden" name="back" value="<?= e('id=' . $sel['id']) ?>">
          <div class="small"><b><?= e(ucfirst($l['channel'])) ?></b> · <?= e($l['pname'] ?: '') ?><?= $l['area'] ? ' · ' . e($l['area']) : '' ?><div class="xs muted"><?= nice_date($l['created_at'], true) ?><?= $l['note'] ? ' · ' . e($l['note']) : '' ?></div></div>
          <select name="status" onchange="this.form.submit()" style="width:auto;padding:5px 8px;font-size:12px"><?php foreach (['new', 'contacted', 'converted', 'closed'] as $s): ?><option <?= $l['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach ?></select></form>
      <?php endforeach ?>
      <?php foreach ($qts as $x): ?><a class="row sb small" style="padding:10px 0;border-top:1px solid #f2eee7" href="<?= url('admin/quotations/' . $x['id']) ?>"><span><b><?= e($x['number']) ?></b> · <?= inr($x['total']) ?></span><?= status_badge($x['status']) ?></a><?php endforeach ?>
    </div>
    <?php endif ?>
    <div class="card pad">
      <h3 style="margin-bottom:12px">Customer scans · this week</h3>
      <?php foreach ($scanTop as $r): ?>
        <div style="margin-bottom:9px"><div class="row sb small"><span><?= e($r['name']) ?></span><b><?= $r['n'] ?></b></div>
          <div style="height:7px;background:#f3efe8;border-radius:4px"><div style="height:7px;border-radius:4px;background:linear-gradient(90deg,#d4ab5e,#b8893a);width:<?= $r['n'] / $maxS * 100 ?>%"></div></div></div>
      <?php endforeach ?>
      <?php if (!$scanTop): ?><p class="small muted">No customer scans this week.</p><?php endif ?>
    </div>
    <div class="card pad">
      <h3 style="margin-bottom:12px">Scan → enquiry (7 days)</h3>
      <dl class="kv"><dt>Customer scans</dt><dd><?= $custScans ?></dd>
        <dt>→ WhatsApp / call</dt><dd><?= $contacts ?> <span class="muted">(<?= $custScans ? round($contacts / $custScans * 100) : 0 ?>%)</span></dd>
        <dt>→ Quotation requests</dt><dd><?= $requests ?></dd>
        <dt>Staff scan → quotation</dt><dd><?= $staffScans ? round($staffQuotes / $staffScans * 100) : 0 ?>% <span class="muted">(<?= $staffQuotes ?> of <?= $staffScans ?>)</span></dd></dl>
    </div>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
