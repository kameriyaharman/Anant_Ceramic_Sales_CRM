<?php
$me = require_admin();
if (is_post()) {
    csrf_check();
    $act = input('action');
    if ($act === 'company') {
        foreach (['company_name', 'company_address', 'company_phone', 'company_whatsapp', 'company_website', 'company_gstin', 'showroom', 'qr_base_url', 'low_stock_boxes', 'wastage_pct'] as $k)
            set_setting($k, trim((string)input($k)));
        set_setting('company_whatsapp', preg_replace('/\D/', '', (string)input('company_whatsapp')));
        flash('Settings saved.');
    }
    if ($act === 'account') {
        $email = trim((string)input('email'));
        $new = (string)input('new_password');
        if (!password_verify((string)input('current_password'), $me['password_hash'])) { flash('Current password is wrong.', 'err'); redirect('admin/settings'); }
        $data = ['name' => trim((string)input('name')) ?: 'Admin'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) $data['email'] = $email;
        if ($new !== '') { if (strlen($new) < 8) { flash('New password must be at least 8 characters.', 'err'); redirect('admin/settings'); } $data['password_hash'] = password_hash($new, PASSWORD_DEFAULT); }
        update('admins', $data, 'id = ?', [$me['id']]);
        flash('Account updated.');
    }
    if ($act === 'add_admin') {
        $email = trim((string)input('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string)input('password')) < 8) { flash('Enter a valid email and a password of 8+ characters.', 'err'); redirect('admin/settings'); }
        if (val('SELECT id FROM admins WHERE email = ?', [$email])) { flash('That email is already an admin.', 'err'); redirect('admin/settings'); }
        insert('admins', ['name' => trim((string)input('name')) ?: 'Admin', 'email' => $email, 'password_hash' => password_hash((string)input('password'), PASSWORD_DEFAULT)]);
        flash('Admin added.');
    }
    if ($act === 'category') {
        $grp = in_array(input('grp'), ['tiles', 'sanitary', 'fittings'], true) ? input('grp') : 'tiles';
        $name = trim((string)input('name'));
        if ($name !== '') insert('categories', ['grp' => $grp, 'name' => $name, 'unit' => input('unit') === 'pc' ? 'pc' : 'sqft', 'code_prefix' => strtoupper(trim((string)input('code_prefix'))) ?: 'AC-1']);
        flash('Category added.');
    }
    if ($act === 'cat_update') {
        foreach ((array)input('cat', []) as $cid => $c) {
            update('categories', ['name' => trim((string)$c['name']), 'unit' => ($c['unit'] ?? '') === 'pc' ? 'pc' : 'sqft', 'code_prefix' => strtoupper(trim((string)$c['code_prefix'])) ?: 'AC-1'], 'id = ?', [(int)$cid]);
        }
        flash('Categories saved.');
    }
    redirect('admin/settings');
}
$cats = rows('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.active = 1) n FROM categories c ORDER BY FIELD(grp,"tiles","sanitary","fittings"), name');
$admins = rows('SELECT id, name, email FROM admins');
$title = 'Settings'; $active = 'settings';
require ROOT . '/views/admin/top.php';
$s = fn($k) => e(setting($k));
?>
<div class="grid g2" style="align-items:start">
  <form class="card pad" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="company">
    <h3 style="margin-bottom:14px">Company &amp; quotation header</h3>
    <div class="field"><label class="f">Company name</label><input name="company_name" value="<?= $s('company_name') ?>"></div>
    <div class="field"><label class="f">Address</label><input name="company_address" value="<?= $s('company_address') ?>"></div>
    <div class="fgrid" style="grid-template-columns:1fr 1fr">
      <div class="field"><label class="f">Phone (shown to customers)</label><input name="company_phone" value="<?= $s('company_phone') ?>"></div>
      <div class="field"><label class="f">WhatsApp number (with 91)</label><input name="company_whatsapp" value="<?= $s('company_whatsapp') ?>"></div>
      <div class="field"><label class="f">Website</label><input name="company_website" value="<?= $s('company_website') ?>"></div>
      <div class="field"><label class="f">GSTIN</label><input name="company_gstin" value="<?= $s('company_gstin') ?>"></div>
    </div>
    <div class="field"><label class="f">Showroom (shown after customer request)</label><input name="showroom" value="<?= $s('showroom') ?>"></div>
    <div class="field"><label class="f">QR link base URL</label><input name="qr_base_url" value="<?= $s('qr_base_url') ?>" placeholder="<?= e(rtrim(abs_url('q'), '/')) ?>">
      <p class="xs muted" style="margin-top:5px">Leave blank to use this site (<?= e(rtrim(abs_url('q'), '/')) ?>/AC1001). Set this <b>before printing labels</b> — e.g. https://crm.anantceramic.com/q once the domain is live.</p></div>
    <div class="fgrid" style="grid-template-columns:1fr 1fr">
      <div class="field"><label class="f">“Low stock” below (boxes)</label><input name="low_stock_boxes" type="number" value="<?= $s('low_stock_boxes') ?>"></div>
      <div class="field"><label class="f">Default wastage % for area</label><input name="wastage_pct" type="number" step="0.5" value="<?= $s('wastage_pct') ?>"></div>
    </div>
    <button class="btn gold">Save settings</button>
  </form>

  <div class="stack">
    <form class="card pad" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cat_update">
      <h3 style="margin-bottom:12px">Categories</h3>
      <table class="t"><thead><tr><th>Group</th><th>Name</th><th>Sold per</th><th>Code prefix</th><th></th></tr></thead><tbody>
      <?php foreach ($cats as $c): ?><tr><td class="small"><?= e(ucfirst($c['grp'])) ?></td><td><input name="cat[<?= $c['id'] ?>][name]" value="<?= e($c['name']) ?>" style="padding:6px 8px"></td>
        <td><select name="cat[<?= $c['id'] ?>][unit]" style="padding:6px"><option value="sqft">sq.ft</option><option value="pc" <?= $c['unit'] === 'pc' ? 'selected' : '' ?>>piece</option></select></td>
        <td><input name="cat[<?= $c['id'] ?>][code_prefix]" value="<?= e($c['code_prefix']) ?>" style="width:80px;padding:6px 8px"></td><td class="xs muted"><?= $c['n'] ?></td></tr><?php endforeach ?>
      </tbody></table>
      <button class="btn sm dark" style="margin-top:10px">Save categories</button>
    </form>
    <form class="card pad" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="category">
      <h3 style="margin-bottom:12px">Add category</h3>
      <div class="row wrap"><select name="grp" style="width:auto"><option value="tiles">Tiles</option><option value="sanitary">Sanitary</option><option value="fittings">Fittings</option></select>
        <input name="name" placeholder="e.g. Outdoor / Parking" style="width:170px"><select name="unit" style="width:auto"><option value="sqft">per sq.ft</option><option value="pc">per piece</option></select>
        <input name="code_prefix" placeholder="AC-1" style="width:80px"><button class="btn sm">Add</button></div>
    </form>
    <form class="card pad" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="account">
      <h3 style="margin-bottom:12px">My admin account</h3>
      <div class="fgrid" style="grid-template-columns:1fr 1fr"><div class="field"><label class="f">Name</label><input name="name" value="<?= e($me['name']) ?>"></div><div class="field"><label class="f">Email</label><input name="email" type="email" value="<?= e($me['email']) ?>"></div>
        <div class="field"><label class="f">Current password</label><input name="current_password" type="password" required></div><div class="field"><label class="f">New password</label><input name="new_password" type="password" placeholder="leave blank to keep"></div></div>
      <button class="btn dark sm">Update account</button>
    </form>
    <form class="card pad" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add_admin">
      <h3 style="margin-bottom:6px">Admins</h3>
      <?php foreach ($admins as $a): ?><div class="small" style="padding:4px 0"><?= e($a['name']) ?> · <span class="muted"><?= e($a['email']) ?></span></div><?php endforeach ?>
      <div class="row wrap" style="margin-top:10px"><input name="name" placeholder="Name" style="width:120px"><input name="email" type="email" placeholder="Email" style="width:170px"><input name="password" type="password" placeholder="Password" style="width:130px"><button class="btn sm">Add admin</button></div>
    </form>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
