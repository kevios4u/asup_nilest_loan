<?php
$filter = (string)($_GET['status'] ?? 'all');
$validFilters = ['pending', 'active', 'rejected', 'terminated', 'all'];
if (!in_array($filter, $validFilters, true)) $filter = 'all';
$sql = 'SELECT m.*, u.email FROM members m JOIN users u ON u.id = m.user_id';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE m.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY m.joined_at DESC LIMIT 250';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editingMember = null;
if ($editId > 0) {
    $stmt = db()->prepare('SELECT m.*, u.email FROM members m JOIN users u ON u.id = m.user_id WHERE m.id = ?');
    $stmt->execute([$editId]);
    $editingMember = $stmt->fetch() ?: null;
}
$memberStatuses = ['pending', 'active', 'rejected', 'terminated'];
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Member management</h1><p>Review applications and manage membership status.</p></div></div>
<div class="member-registration-grid">
<section class="panel form-panel">
    <div class="section-heading"><div><p class="eyebrow">Individual member</p><h2><?= $editingMember ? 'Update member details' : 'Add a member' ?></h2></div><?php if ($editingMember): ?><a class="text-link" href="index.php?page=members">Cancel editing</a><?php endif; ?></div>
    <p class="muted"><?= $editingMember ? 'Correct a member record and save the revised details.' : 'Create one member account without preparing a CSV roster.' ?></p>
    <form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="<?= $editingMember ? 'update_member' : 'create_member' ?>"><?php if ($editingMember): ?><input type="hidden" name="member_id" value="<?= (int)$editingMember['id'] ?>"><?php endif; ?>
        <div class="form-row"><label>Full name<input name="full_name" maxlength="190" value="<?= e($editingMember['full_name'] ?? '') ?>" required></label><label>Staff ID<input name="staff_id" maxlength="80" value="<?= e($editingMember['staff_id'] ?? '') ?>" required></label></div>
        <div class="form-row"><label>Email address<input name="email" type="email" maxlength="190" value="<?= e($editingMember['email'] ?? '') ?>" required></label><label>Phone number<input name="phone" type="tel" maxlength="40" value="<?= e($editingMember['phone'] ?? '') ?>" required></label></div>
        <div class="form-row"><label>Department<input name="department" maxlength="150" value="<?= e($editingMember['department'] ?? '') ?>"></label><label>Monthly contribution (₦)<input name="monthly_contribution" type="number" min="0.01" step="0.01" value="<?= e($editingMember['monthly_contribution'] ?? '') ?>" required></label></div>
        <div class="form-row"><label>Membership status<select name="status" required><?php foreach ($memberStatuses as $status): ?><?php if (!$editingMember && !in_array($status, ['pending', 'active'], true)) continue; ?><option value="<?= e($status) ?>"<?= ($editingMember['status'] ?? 'pending') === $status ? ' selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label><?php if (!$editingMember): ?><label>Initial password<input name="password" type="password" minlength="10" autocomplete="new-password" required><small>At least 10 characters.</small></label><?php endif; ?></div>
        <button class="button button-primary" type="submit"><?= $editingMember ? 'Save member changes' : 'Create member account' ?></button>
    </form>
</section>
<section class="panel form-panel"><h2>Bulk register members</h2><p class="muted">Upload a CSV roster using the required header: staff_id, full_name, email, phone, department, monthly_contribution, password. Each password must be at least 10 characters. New accounts require administrator approval.</p><form method="post" enctype="multipart/form-data" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="bulk_members"><label>Member roster CSV<input type="file" name="members_csv" accept=".csv,text/csv" required></label><button class="button button-primary" type="submit">Validate and register members</button></form></section>
</div>
<div class="filter-tabs"><?php foreach ($validFilters as $option): ?><a class="<?= $filter === $option ? 'active' : '' ?>" href="index.php?page=members&status=<?= e($option) ?>"><?= e(ucfirst($option)) ?></a><?php endforeach; ?></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Member</th><th>Staff ID</th><th>Contact</th><th>Department</th><th>Monthly contribution</th><th>Applied</th><th>Status / action</th></tr></thead><tbody>
<?php if (!$members): ?><tr><td colspan="7" class="empty-state">No <?= e($filter === 'all' ? '' : $filter) ?> members found.</td></tr><?php endif; ?>
<?php foreach ($members as $item): ?><tr><td><strong><?= e($item['full_name']) ?></strong><small class="table-subtitle"><?= e($item['email']) ?></small></td><td><?= e($item['staff_id']) ?></td><td><?= e($item['phone']) ?></td><td><?= e($item['department'] ?: '—') ?></td><td><?= e(money($item['monthly_contribution'])) ?></td><td><?= e(date('M j, Y', strtotime($item['joined_at']))) ?></td><td><?= status_badge($item['status']) ?>
<div class="inline-actions"><a class="button button-outline button-tiny" href="index.php?page=members&status=<?= e($filter) ?>&edit=<?= (int)$item['id'] ?>">Edit</a><a class="button button-outline button-tiny" href="index.php?page=requests&member_id=<?= (int)$item['id'] ?>">View history</a><?php if ($item['status'] === 'pending'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="approve_member"><input type="hidden" name="member_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="decision" value="active"><button class="button button-primary button-tiny" type="submit">Approve</button></form><form method="post" data-confirm="Reject this membership application?"><?= csrf_field() ?><input type="hidden" name="action" value="approve_member"><input type="hidden" name="member_id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="button button-danger button-tiny" type="submit">Reject</button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
