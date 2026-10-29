<?php
if ($user['role'] !== 'member' || !in_array($user['member_status'], ['active', 'terminated'], true)) {
    echo '<div class="notice-box">An approved member account is required to view a savings statement.</div>';
} else {
    $stmt = db()->prepare("SELECT transaction_ref, transaction_type, amount, contribution_month, transaction_date, authorization_details, payment_reference
                           FROM financial_ledger WHERE member_id = ? AND status = 'posted'
                           ORDER BY transaction_date ASC, id ASC");
    $stmt->execute([$user['member_id']]);
    $transactions = $stmt->fetchAll();
    $balance = savings_balance((int)$user['member_id']);
?>
<section class="dashboard-heading"><div><p class="eyebrow">Member savings</p><h1>Your savings statement</h1><p>Transactions posted to your member savings ledger.</p></div><div class="balance-chip"><span>Current savings</span><strong><?= e(money($balance)) ?></strong></div></section>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Transaction date</th><th>Contribution month</th><th>Reference</th><th>Description</th><th>Amount</th><th>Balance</th></tr></thead><tbody>
<?php if (!$transactions): ?><tr><td colspan="6" class="empty-state">No savings transactions have been posted yet.</td></tr><?php endif; ?>
<?php $running = 0.0; foreach ($transactions as $item): 
    $net = ($item['transaction_type'] === 'withdrawal') ? - (float)$item['amount'] : (float)$item['amount'];
    $running += $net;
?>
<tr><td><?= e(date('M j, Y', strtotime($item['transaction_date']))) ?></td><td><?= $item['contribution_month'] ? e(date('F Y', strtotime($item['transaction_date']))) : '—' ?></td><td class="mono"><?= e($item['transaction_ref']) ?></td><td><?= e(ucwords(str_replace('_', ' ', $item['transaction_type']))) ?><small class="table-subtitle"><?= e($item['authorization_details']) ?></small></td><td class="<?= $item['transaction_type'] === 'withdrawal' ? 'amount-negative' : 'amount-positive' ?>"><?= $item['transaction_type'] === 'withdrawal' ? '−' : '+' ?><?= e(money($item['amount'])) ?></td><td><strong><?= e(money($running)) ?></strong></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
<?php } ?>
