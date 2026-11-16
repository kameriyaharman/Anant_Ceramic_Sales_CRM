<?php
require_admin();
$roles = rows('SELECT * FROM roles ORDER BY id');
if (is_post()) {
    csrf_check();
    $act = input('action');
    if ($act === 'staff') {
        $sid = (int)input('id');
        $mobile = clean_mobile((string)input('mobile'));
        $data = ['name' => trim((string)input('name')), 'mobile' => $mobile, 'role_id' => (int)input('role_id') ?: null,
            'showroom' => trim((string)input('showroom')) ?: null, 'status' => input('status') === 'blocked' ? 'blocked' : 'active'];
        $pin = trim((string)input('pin'));
        if ($data['name'] === '' || strlen($mobile) !== 10) { flash('Enter a name and a 10-digit mobile number.', 'err'); redirect('admin/staff' . ($sid ? "?id=$sid" : '?new=1')); }
        if ($pin !== '' && !preg_match('/^\d{4}$/', $pin)) { flash('PIN must be 4 digits.', 'err'); redirect('admin/staff' . ($sid ? "?id=$sid" : '?new=1')); }
        if (val('SELECT id FROM staff WHERE mobile = ? AND id <> ?', [$mobile, $sid])) { flash('Another staff member already uses this mobile.', 'err'); redirect('admin/staff' . ($sid ? "?id=$sid" : '?new=1')); }
        if ($pin !== '') $data['pin_hash'] = password_hash($pin, PASSWORD_DEFAULT);
        if ($sid) { update('staff', $data, 'id = ?', [$sid]); flash('Staff saved.' . ($pin ? ' New PIN set.' : '')); }
        else {
            if ($pin === '') { flash('Set a 4-digit PIN for the new staff member.', 'err'); redirect('admin/staff?new=1'); }
            $sid = insert('staff', $data); flash('Staff added. They can now log in to the staff app with their mobile and PIN.');
        }
        redirect('admin/staff?id=' . $sid);
    }
    if ($act === 'role') {
        $rid = (int)input('role_id');
        $perms = []; foreach (array_keys(perm_labels()) as $k) $perms[$k] = empty($_POST['perm'][$k]) ? 0 : 1;
        $data = ['name' => trim((string)input('role_name')) ?: 'Role', 'max_discount' => max(0, min(100, (float)input('max_discount'))), 'perms' => json_encode($perms)];
        if ($rid) update('roles', $data, 'id = ?', [$rid]); else $rid = insert('roles', $data);
        flash('Role “' . $data['name'] . '” saved.');
        redirect('admin/staff?role=' . $rid . (input('id') ? '&id=' . (int)input('id') : ''));
    }
}

$today = date('Y-m-d');
$list = rows('SELECT s.*, r.name role_name, r.max_discount,
    (SELECT COUNT(*) FROM scans x WHERE x.staff_id = s.id AND DATE(x.created_at) = ?) scans,
    (SELECT COUNT(*) FROM quotations q WHERE q.staff_id = s.id AND DATE(q.created_at) = ? AND q.status <> "draft") quotes
    FROM staff s LEFT JOIN roles r ON r.id = s.role_id ORDER BY s.status, s.id', [$today, $today]);
$sel = input('id') ? row('SELECT * FROM staff WHERE id = ?', [(int)input('id')]) : null;
$isNew = input('new') === '1';
$roleSel = null;
$rid = (int)input('role', $sel['role_id'] ?? ($roles[0]['id'] ?? 0));
if (input('role') === 'new') $roleSel = ['id' => 0, 'name' => '', 'max_discount' => 0, 'perms' => json_encode(default_perms())];
else foreach ($roles as $r) if ((int)$r['id'] === $rid) $roleSel = $r;
$perm = $roleSel ? array_merge(default_perms(), json_decode($roleSel['perms'] ?: '{}', true) ?: []) : default_perms();

$title = 'Staff & permissions'; $active = 'staff';
$actions = '<a class="btn gold" href="' . url('admin/staff?new=1') . '">' . icon('plus') . 'Add staff</a>';
require ROOT . '/views/admin/top.php';
?>
<div class="grid" style="grid-template-columns:1.5fr 1fr;align-items:start">
  <div class="card"><div class="tbl-wrap"><table class="t">
    <thead><tr><th>Staff</th><th>Role</th><th>Login</th><th>Max discount</th><th>Today</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($list as $s): ?>
      <tr class="click" onclick="location='<?= url('admin/staff?id=' . $s['id']) ?>'" style="<?= ($sel['id'] ?? 0) == $s['id'] ? 'background:#fcf7ec' : '' ?>">
        <td><div class="row"><span class="av"><?= e(initials($s['name'])) ?></span><div><div class="b"><?= e($s['name']) ?></div><div class="xs muted"><?= e(mask_mobile($s['mobile'])) ?></div></div></div></td>
        <td class="small"><?= e($s['role_name'] ?? '—') ?></td>
        <td class="small muted">App · PIN</td>
        <td><?= ($s['max_discount'] ?? 0) > 0 ? num($s['max_discount']) . '%' : '—' ?></td>
        <td class="small"><?= $s['scans'] ?> scans<?= $s['quotes'] ? ' · ' . $s['quotes'] . ' QT' : '' ?></td>
        <td><?= $s['status'] === 'active' ? '<span class="badge b-green">Active</span>' : '<span class="badge b-red">Blocked</span>' ?></td>
      </tr>
    <?php endforeach ?></tbody>
  </table></div>
  <?php if (!$list): ?><div class="empty">No staff yet. Click “Add staff”.</div><?php endif ?>
  </div>

  <div class="stack">
    <?php if ($sel || $isNew): $s = $sel ?: ['id' => 0, 'name' => '', 'mobile' => '', 'role_id' => $roles[0]['id'] ?? null, 'showroom' => 'Showroom · Chikhli', 'status' => 'active']; ?>
    <form class="card pad" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="staff"><input type="hidden" name="id" value="<?= $s['id'] ?>">
      <h3 style="margin-bottom:14px"><?= $s['id'] ? 'Edit staff' : 'Add staff' ?></h3>
      <div class="field"><label class="f">Name</label><input name="name" value="<?= e($s['name']) ?>" required></div>
      <div class="field"><label class="f">Mobile (login)</label><input name="mobile" type="tel" value="<?= e($s['mobile']) ?>" placeholder="10-digit mobile" required></div>
      <div class="field"><label class="f"><?= $s['id'] ? 'Reset PIN (leave blank to keep)' : '4-digit PIN' ?></label><input name="pin" inputmode="numeric" maxlength="4" pattern="\d{4}" placeholder="••••" autocomplete="off"></div>
      <div class="fgrid" style="grid-template-columns:1fr 1fr">
        <div class="field"><label class="f">Role</label><select name="role_id"><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= $r['id'] == $s['role_id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach ?></select></div>
        <div class="field"><label class="f">Status</label><select name="status"><option value="active">Active</option><option value="blocked" <?= $s['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option></select></div>
      </div>
      <div class="field"><label class="f">Showroom / branch</label><input name="showroom" value="<?= e($s['showroom']) ?>"></div>
      <button class="btn gold block">Save staff</button>
    </form>
    <?php endif ?>

    <?php if ($roleSel): ?>
    <form class="card pad" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="role_id" value="<?= $roleSel['id'] ?>"><input type="hidden" name="id" value="<?= $sel['id'] ?? '' ?>">
      <div class="row sb" style="margin-bottom:12px"><h3>Edit role · <?= e($roleSel['name'] ?: 'New role') ?></h3>
        <select style="width:auto;padding:5px 8px;font-size:12px" onchange="location='<?= url('admin/staff') ?>?role='+this.value<?= $sel ? "+'&id=" . $sel['id'] . "'" : '' ?>">
          <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= $r['id'] == $roleSel['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach ?><option value="new" <?= !$roleSel['id'] ? 'selected' : '' ?>>+ New role</option></select></div>
      <div class="field"><label class="f">Role name</label><input name="role_name" value="<?= e($roleSel['name']) ?>" required></div>
      <?php foreach (perm_labels() as $k => $l): ?>
        <div class="row sb" style="padding:7px 0;border-bottom:1px solid #f2eee7"><span class="small"><?= e($l) ?></span><?= switch_input("perm[$k]", (bool)$perm[$k]) ?></div>
      <?php endforeach ?>
      <div class="row sb" style="padding:10px 0"><span class="small">Give discount without approval up to</span><div class="row" style="gap:4px"><input name="max_discount" type="number" step="0.5" min="0" max="100" value="<?= e(num($roleSel['max_discount'])) ?>" style="width:70px"><b>%</b></div></div>
      <p class="xs muted" style="margin-bottom:12px">Above this limit (or below the product’s minimum selling price) the quotation is sent to admin for approval.</p>
      <button class="btn dark block">Save role</button>
    </form>
    <?php endif ?>
  </div>
</div>
<?php require ROOT . '/views/admin/bottom.php';
