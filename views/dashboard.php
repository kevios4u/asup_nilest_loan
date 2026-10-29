<?php if ($user['role'] === 'admin'):
    $stats = [
        'members' => (int)db()->query("SELECT COUNT(*) FROM members WHERE status = 'active'")->fetchColumn(),
        'pending' => (int)db()->query("SELECT COUNT(*) FROM members WHERE status = 'pending'")->fetchColumn(),
        'savings' => (float)db()->query(
            "SELECT COALESCE(SUM(CASE WHEN transaction_type = 'withdrawal' THEN -amount
                WHEN transaction_type IN ('contribution', 'adjustment') THEN amount ELSE 0 END), 0)
             FROM financial_ledger WHERE status = 'posted'"
        )->fetchColumn(),
        'requests' => (int)db()->query("SELECT COUNT(*) FROM member_requests WHERE status = 'pending'")->fetchColumn(),
        'loans' => (int)db()->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'")->fetchColumn(),
    ];
    $latestImports = db()->query('SELECT id, file_name, contribution_month, status, total_rows, error_rows, created_at FROM csv_imports ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Good <?= e(date('A') === 'AM' ? 'morning' : 'day') ?>, Administrator</h1><p>Here is the latest overview of the scheme.</p></div><a class="button button-primary" href="index.php?page=contributions">Upload contributions</a></div>
<section class="stats-grid"><article class="stat-card"><span>Active members</span><strong><?= number_format($stats['members']) ?></strong><small>Approved memberships</small></article><article class="stat-card"><span>Pending members</span><strong><?= number_format($stats['pending']) ?></strong><small><a href="index.php?page=members">Review applications →</a></small></article><article class="stat-card"><span>Total net savings</span><strong><?= e(money($stats['savings'])) ?></strong><small>Posted savings ledger balance</small></article><article class="stat-card"><span>Pending requests</span><strong><?= number_format($stats['requests']) ?></strong><small><a href="index.php?page=requests">Member requests to review →</a></small></article><article class="stat-card"><span>Loan applications</span><strong><?= number_format($stats['loans']) ?></strong><small>Awaiting a decision</small></article></section>
<section class="section-block panel"><div class="section-heading"><div><p class="eyebrow">Recent activity</p><h2>Contribution imports</h2></div><a class="text-link" href="index.php?page=contributions">All imports →</a></div>
<?php if ($latestImports): ?><div class="table-wrap"><table><thead><tr><th>File</th><th>Month</th><th>Rows</th><th>Errors</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($latestImports as $item): ?><tr><td><?= e($item['file_name']) ?></td><td><?= e(date('F Y', strtotime($item['contribution_month']))) ?></td><td><?= number_format((int)$item['total_rows']) ?></td><td><?= number_format((int)$item['error_rows']) ?></td><td><?= status_badge($item['status']) ?></td><td><a class="text-link" href="index.php?page=<?= $item['status'] === 'preview' ? 'import_preview' : 'import_summary' ?>&id=<?= (int)$item['id'] ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty-state">No CSV imports yet. Upload the latest salary deductions to get started.</p><?php endif; ?></section>
<?php else:
    $memberId = (int)$user['member_id'];
    if ($user['member_status'] !== 'active'): ?>
        <section class="pending-card"><span class="pending-icon">◷</span><p class="eyebrow">Membership status</p><h1>Your membership is <?= e($user['member_status']) ?>.</h1><p><?php if ($user['member_status'] === 'pending'): ?>Your member services will be available after the scheme administrator reviews your application.<?php elseif ($user['member_status'] === 'terminated'): ?>Your membership has ended. You can still review your saved statements, requests, loans, and notifications.<?php else: ?>Your membership application was not approved. Contact the scheme administrator if you need assistance.<?php endif; ?></p><?php if ($user['member_status'] === 'terminated'): ?><div class="button-row center-actions"><a class="button button-outline" href="index.php?page=statement">Savings statement</a><a class="button button-outline" href="index.php?page=requests">Request history</a><a class="button button-outline" href="index.php?page=loans">Loan history</a></div><?php else: ?><a href="index.php?page=contact" class="text-link">Need help? Contact the scheme →</a><?php endif; ?></section>
    <?php else:
        $balance = savings_balance($memberId);
        $outstanding = outstanding_loan_balance($memberId);
        $stmt = db()->prepare("SELECT COUNT(*) FROM member_requests WHERE member_id = ? AND status = 'pending'");
        $stmt->execute([$memberId]);
        $pendingRequests = (int)$stmt->fetchColumn();
        $stmt = db()->prepare('SELECT transaction_type, amount, transaction_date, transaction_ref FROM financial_ledger WHERE member_id = ? AND status = \'posted\' ORDER BY transaction_date DESC, id DESC LIMIT 5');
        $stmt->execute([$memberId]);
        $recent = $stmt->fetchAll();
        $stmt = db()->prepare('SELECT title, body, created_at FROM announcements WHERE published = 1 ORDER BY created_at DESC LIMIT 3');
        $announcements = $stmt->fetchAll();
    ?>
        <div class="dashboard-heading"><div><p class="eyebrow">Member portal</p><h1>Welcome, <?= e(explode(' ', trim($user['full_name']))[0]) ?>.</h1><p>Your savings journey, clearly in view.</p></div><a class="button button-primary" href="index.php?page=requests">Make a request</a></div>
        <section class="stats-grid member-stats"><article class="stat-card stat-highlight"><span>Available savings</span><strong><?= e(money($balance)) ?></strong><small>At least ₦1,000 must remain after a withdrawal</small></article><article class="stat-card"><span>Monthly contribution</span><strong><?= e(money($user['monthly_contribution'])) ?></strong><small>Current approved amount</small></article><article class="stat-card"><span>Outstanding loans</span><strong><?= e(money($outstanding)) ?></strong><small>Loan repayments recorded</small></article><article class="stat-card"><span>Pending requests</span><strong><?= number_format($pendingRequests) ?></strong><small><a href="index.php?page=requests">View request history →</a></small></article></section>
        <section class="section-block panel"><div class="section-heading"><div><p class="eyebrow">Your account</p><h2>Recent transactions</h2></div><a class="text-link" href="index.php?page=statement">Full savings statement →</a></div>
            <?php if ($recent): ?><div class="table-wrap"><table><thead><tr><th>Reference</th><th>Type</th><th>Date</th><th>Amount</th></tr></thead><tbody><?php foreach ($recent as $item): ?><tr><td class="mono"><?= e(substr($item['transaction_ref'], 0, 12)) ?>…</td><td><?= e(ucwords(str_replace('_', ' ', $item['transaction_type']))) ?></td><td><?= e(date('M j, Y', strtotime($item['transaction_date']))) ?></td><td><?= e(money($item['amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty-state">Your transactions will appear here after contributions are posted.</p><?php endif; ?>
        </section>
        <?php if ($announcements): ?><section class="section-block"><div class="section-heading"><div><p class="eyebrow">Scheme updates</p><h2>Announcements</h2></div></div><div class="announcement-grid"><?php foreach ($announcements as $item): ?><article class="announcement-card"><time><?= e(date('M j, Y', strtotime($item['created_at']))) ?></time><h3><?= e($item['title']) ?></h3><p><?= nl2br(e($item['body'])) ?></p></article><?php endforeach; ?></div></section><?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
