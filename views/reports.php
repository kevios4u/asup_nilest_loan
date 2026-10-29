<?php
$selectedMonth = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) $selectedMonth = date('Y-m');
$monthDate = $selectedMonth . '-01';
$monthCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $monthDate);
if (!$monthCheck || $monthCheck->format('Y-m-d') !== $monthDate) {
    $selectedMonth = date('Y-m');
    $monthDate = $selectedMonth . '-01';
}
$stmt = db()->prepare(
    "SELECT COUNT(*) AS active_count,
            COALESCE(SUM(m.monthly_contribution), 0) AS expected_total,
            COALESCE(SUM(CASE WHEN m.monthly_contribution > COALESCE(l.paid, 0) THEN 1 ELSE 0 END), 0) AS members_without_payment,
            COALESCE(SUM(GREATEST(m.monthly_contribution - COALESCE(l.paid, 0), 0)), 0) AS outstanding_total
     FROM members m LEFT JOIN (
        SELECT member_id, SUM(amount) AS paid FROM financial_ledger
        WHERE transaction_type = 'contribution' AND contribution_month = ? AND status = 'posted'
        GROUP BY member_id
     ) l ON l.member_id = m.id WHERE m.status = 'active'"
);
$stmt->execute([$monthDate]);
$outstanding = $stmt->fetch();
$stmt = db()->prepare(
    "SELECT l.*, m.staff_id, m.full_name FROM financial_ledger l JOIN members m ON m.id = l.member_id
     WHERE l.transaction_type = 'contribution' AND l.contribution_month = ? AND l.status = 'posted'
     ORDER BY m.full_name LIMIT 500"
);
$stmt->execute([$monthDate]);
$monthlyContributions = $stmt->fetchAll();
$monthlyActual = array_sum(array_map(static fn(array $row): float => (float)$row['amount'], $monthlyContributions));
$byMonth = db()->query(
    "SELECT contribution_month, COUNT(*) AS entries, SUM(amount) AS total
     FROM financial_ledger WHERE transaction_type = 'contribution' AND status = 'posted'
     GROUP BY contribution_month ORDER BY contribution_month DESC LIMIT 12"
)->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Financial reports</h1><p>Review monthly contribution records and trends.</p></div></div>
<section class="panel form-panel report-filter"><form method="get" class="form-row"><input type="hidden" name="page" value="reports"><label>Contribution month<input type="month" name="month" value="<?= e($selectedMonth) ?>"></label><button class="button button-primary" type="submit">View month</button></form></section>
<section class="stats-grid">
    <article class="stat-card">
        <span>Active members</span>
        <strong><?= number_format((int)$outstanding['active_count']) ?></strong>
        <small>Currently approved members</small>
    </article>
    <article class="stat-card">
        <span>Expected for <?= e(date('F Y', strtotime($monthDate))) ?></span>
        <strong><?= e(money($outstanding['expected_total'])) ?></strong>
        <small>Combined monthly contributions expected from all active members</small>
    </article>
    <article class="stat-card">
        <span>Received and posted</span>
        <strong><?= e(money($monthlyActual)) ?></strong>
        <small><?= number_format(count($monthlyContributions)) ?> posted <?= count($monthlyContributions) === 1 ? 'entry' : 'entries' ?> recorded for <?= e(date('F Y', strtotime($monthDate))) ?></small>
    </article>
</section>
<section class="section-block panel"><div class="section-heading"><div><p class="eyebrow"><?= e(date('F Y', strtotime($monthDate))) ?></p><h2>Monthly contribution records</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>Member</th><th>Staff ID</th><th>Transaction date</th><th>Contribution</th></tr></thead><tbody><?php if (!$monthlyContributions): ?><tr><td colspan="4" class="empty-state">No posted contributions for this month.</td></tr><?php endif; ?><?php foreach ($monthlyContributions as $item): ?><tr><td><?= e($item['full_name']) ?></td><td><?= e($item['staff_id']) ?></td><td><?= e(date('M j, Y', strtotime($item['transaction_date']))) ?></td><td><?= e(money($item['amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="section-block panel"><div class="section-heading"><div><p class="eyebrow">Contribution trends</p><h2>Recent monthly totals</h2></div></div><div class="table-wrap"><table><thead><tr><th>Month</th><th>Contribution entries</th><th>Total posted</th></tr></thead><tbody><?php if (!$byMonth): ?><tr><td colspan="3" class="empty-state">No contributions have been recorded.</td></tr><?php endif; ?><?php foreach ($byMonth as $row): ?><tr><td><?= e(date('F Y', strtotime($row['contribution_month']))) ?></td><td><?= number_format((int)$row['entries']) ?></td><td><?= e(money($row['total'])) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
