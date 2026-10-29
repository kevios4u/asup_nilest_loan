<?php
$importId = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM csv_imports WHERE id = ?');
$stmt->execute([$importId]);
$batch = $stmt->fetch();
if (!$batch) {
    http_response_code(404);
    echo '<div class="notice-box">Import not found.</div>';
} else {
    $stmt = db()->prepare(
        'SELECT r.*, m.full_name FROM csv_import_rows r LEFT JOIN members m ON m.id = r.member_id
         WHERE r.import_id = ? ORDER BY r.row_number LIMIT 1000'
    );
    $stmt->execute([$importId]);
    $rows = $stmt->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Contribution import preview</p><h1>Review before importing</h1><p><?= e($batch['file_name']) ?> · <?= e(date('F Y', strtotime($batch['contribution_month']))) ?></p></div><a class="button button-outline" href="index.php?page=contributions">Back to imports</a></div>
<section class="stats-grid"><article class="stat-card"><span>Total records</span><strong><?= number_format((int)$batch['total_rows']) ?></strong></article><article class="stat-card"><span>Valid to import</span><strong><?= number_format((int)$batch['valid_rows']) ?></strong></article><article class="stat-card"><span>Errors excluded</span><strong><?= number_format((int)$batch['error_rows']) ?></strong><small><?php if ((int)$batch['error_rows'] > 0): ?><a href="index.php?page=error_report&id=<?= $importId ?>">Download error report →</a><?php endif; ?></small></article></section>
<?php if ($batch['status'] === 'preview'): ?>
<?php if ((int)$batch['valid_rows'] > 0): ?><form method="post" class="confirm-bar" data-confirm="Import all valid rows into the financial ledger?"><?= csrf_field() ?><input type="hidden" name="action" value="confirm_import"><input type="hidden" name="import_id" value="<?= $importId ?>"><p>Confirming posts <?= number_format((int)$batch['valid_rows']) ?> valid contributions. Rows with errors will not be imported.</p><button class="button button-primary" type="submit">Confirm import</button></form><?php else: ?><div class="notice-box">There are no valid contributions to import. Correct the CSV and upload it again.</div><?php endif; ?>
<?php else: ?><div class="alert alert-success">This import has been completed. Ledger entries have been created for valid records.</div><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>CSV row</th><th>Staff ID</th><th>Member</th><th>Amount</th><th>Validation</th><th>Details</th><?php if ($batch['status'] === 'preview'): ?><th>Action</th><?php endif; ?></tr></thead><tbody><?php if (!$rows): ?><tr><td colspan="<?= $batch['status'] === 'preview' ? 7 : 6 ?>" class="empty-state">No rows remain in this import preview.</td></tr><?php endif; ?><?php foreach ($rows as $row): ?><tr><td><?= (int)$row['row_number'] ?></td><td><?= e($row['staff_id']) ?></td><td><?= e($row['full_name'] ?? '—') ?></td><td><?= $row['amount'] !== null ? e(money($row['amount'])) : '—' ?></td><td><?= status_badge($row['status']) ?></td><td><?= e($row['message'] ?: 'Ready to import') ?></td><?php if ($batch['status'] === 'preview'): ?><td><?php if ($row['status'] === 'error'): ?><form method="post" data-confirm="Remove this errored row from the pending import?"><?= csrf_field() ?><input type="hidden" name="action" value="remove_import_error"><input type="hidden" name="import_id" value="<?= $importId ?>"><input type="hidden" name="row_id" value="<?= (int)$row['id'] ?>"><button class="button button-danger button-tiny" type="submit">Remove</button></form><?php else: ?>—<?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div></section>
<?php } ?>
