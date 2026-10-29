<?php
$types = [
    'contribution_increase' => 'Contribution increase', 'contribution_reduction' => 'Contribution reduction',
    'withdrawal' => 'Partial withdrawal', 'termination' => 'Membership termination',
];
$typeFilter = (string)($_GET['type'] ?? 'all');
if ($typeFilter !== 'all' && !array_key_exists($typeFilter, $types)) $typeFilter = 'all';
$historyMemberId = $user['role'] === 'admin' ? (int)($_GET['member_id'] ?? 0) : (int)$user['member_id'];
if ($user['role'] === 'admin') {
    $sql = 'SELECT r.*, m.full_name, m.staff_id FROM member_requests r JOIN members m ON m.id = r.member_id';
    $params = [];
    $conditions = [];
    if ($historyMemberId > 0) {
        $conditions[] = 'r.member_id = ?';
        $params[] = $historyMemberId;
    }
    if ($typeFilter !== 'all') {
        $conditions[] = 'r.request_type = ?';
        $params[] = $typeFilter;
    }
    if ($conditions) $sql .= ' WHERE ' . implode(' AND ', $conditions);
    $sql .= ' ORDER BY (r.status = \'pending\') DESC, r.created_at DESC LIMIT 250';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} else {
    $sql = 'SELECT * FROM member_requests WHERE member_id = ?';
    $params = [$historyMemberId];
    if ($typeFilter !== 'all') {
        $sql .= ' AND request_type = ?';
        $params[] = $typeFilter;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
}
?>
<div class="dashboard-heading"><div><p class="eyebrow"><?= $user['role'] === 'admin' ? 'Administrator portal' : 'Member portal' ?></p><h1><?= $user['role'] === 'admin' ? 'Member requests' : 'Your requests' ?></h1><p><?= $user['role'] === 'admin' ? 'Review and process member requests.' : 'Submit a request and track administrator decisions.' ?></p></div></div>
<?php if ($user['role'] === 'member' && $user['member_status'] === 'active'): ?>
<section class="panel form-panel"><h2>Submit a request</h2><p class="muted">Withdrawals must leave at least ₦1,000 in your savings. Termination requires full loan clearance.</p>
<form method="post" class="form-stack" data-request-form><?= csrf_field() ?><input type="hidden" name="action" value="member_request">
    <label>Request type<select name="request_type" required data-request-type><option value="">Choose request type</option><?php foreach ($types as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label data-field="proposed_contribution" hidden>New monthly contribution (₦)<input type="number" name="proposed_contribution" min="0.01" step="0.01"></label>
    <label data-field="amount" hidden>Withdrawal amount (₦)<input type="number" name="amount" min="0.01" step="0.01"></label>
    <label>Reason<textarea name="reason" rows="3" maxlength="2000" required></textarea></label>
    <button class="button button-primary" type="submit">Submit for review</button>
</form></section>
<section class="section-block"><div class="section-heading"><div><p class="eyebrow">Request history</p><h2>Your submitted requests</h2></div></div>
<?php endif; ?>
<div class="filter-tabs"><a class="<?= $typeFilter === 'all' ? 'active' : '' ?>" href="index.php?page=requests<?= $historyMemberId > 0 && $user['role'] === 'admin' ? '&member_id=' . $historyMemberId : '' ?>">All requests</a><?php foreach ($types as $value => $label): ?><a class="<?= $typeFilter === $value ? 'active' : '' ?>" href="index.php?page=requests&type=<?= e($value) ?><?= $historyMemberId > 0 && $user['role'] === 'admin' ? '&member_id=' . $historyMemberId : '' ?>"><?= e($label) ?></a><?php endforeach; ?></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><?php if ($user['role'] === 'admin'): ?><th>Member</th><?php endif; ?><th>Reference</th><th>Request</th><th>Details</th><th>Submitted</th><th>Status / action</th></tr></thead><tbody>
<?php if (!$requests): ?><tr><td colspan="<?= $user['role'] === 'admin' ? 6 : 5 ?>" class="empty-state">No requests found.</td></tr><?php endif; ?>
<?php foreach ($requests as $request): ?><tr><?php if ($user['role'] === 'admin'): ?><td><strong><?= e($request['full_name']) ?></strong><small class="table-subtitle"><?= e($request['staff_id']) ?></small></td><?php endif; ?><td class="mono">REQ-<?= str_pad((string)(int)$request['id'], 6, '0', STR_PAD_LEFT) ?></td><td><?= e($types[$request['request_type']] ?? $request['request_type']) ?></td><td><?php if ($request['amount'] !== null): ?>Amount: <?= e(money($request['amount'])) ?><?php elseif ($request['proposed_contribution'] !== null): ?>New monthly contribution: <?= e(money($request['proposed_contribution'])) ?><?php endif; ?><small class="table-subtitle"><?= e($request['reason']) ?></small><?php if (!empty($request['reviewer_notes'])): ?><small class="table-subtitle">Admin note: <?= e($request['reviewer_notes']) ?></small><?php endif; ?></td><td><?= e(date('M j, Y', strtotime($request['created_at']))) ?></td><td><?= status_badge($request['status']) ?>
<?php if ($user['role'] === 'admin' && $request['status'] === 'pending'): ?><form method="post" class="review-form"><?= csrf_field() ?><input type="hidden" name="action" value="review_request"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><input name="reviewer_notes" placeholder="Optional decision note" maxlength="500"><?php if ($request['request_type'] === 'withdrawal'): ?><label>Payment reference (required to approve)<input name="payment_reference" maxlength="190"></label><?php endif; ?><div class="inline-actions"><button class="button button-primary button-tiny" name="decision" value="approved" type="submit">Approve</button><button class="button button-danger button-tiny" name="decision" value="rejected" type="submit">Reject</button></div></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php if ($user['role'] === 'member' && $user['member_status'] === 'active'): ?></section><?php endif; ?>
