<?php
$imports = db()->query(
    'SELECT i.*, u.email FROM csv_imports i JOIN users u ON u.id = i.imported_by ORDER BY i.created_at DESC LIMIT 100'
)->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Monthly contributions</h1><p>Upload, validate, preview, and confirm salary-deduction contribution files.</p></div><a class="button button-outline" href="index.php?page=template">Download CSV template ↓</a></div>
<section class="panel form-panel"><div class="section-heading"><div><p class="eyebrow">New import</p><h2>Upload account department CSV</h2></div></div>
<form method="post" enctype="multipart/form-data" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="import_upload">
    <div class="form-row"><label>Contribution month<input type="month" name="contribution_month" value="<?= e(date('Y-m')) ?>" required></label><label>CSV file<input type="file" name="csv_file" accept=".csv,text/csv" required></label></div>
    <div class="form-row"><label>Staff ID column position (0-based)<input type="number" name="staff_column" min="0" value="0" required></label><label>Contribution amount column position<input type="number" name="amount_column" min="0" value="1" required></label></div>
    <p class="small muted">The first CSV row must contain headers. Column positions start at 0 (the first column is 0). You can download the two-column staff_id, amount template above. Amounts may include commas or a ₦ symbol.</p>
    <button class="button button-primary" type="submit">Validate and preview file</button>
</form></section>
<section class="section-block panel"><div class="section-heading"><div><p class="eyebrow">Import history</p><h2>Previous uploads</h2></div></div>
<div class="table-wrap"><table><thead><tr><th>File</th><th>Contribution month</th><th>Uploaded</th><th>Records</th><th>Errors</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if (!$imports): ?><tr><td colspan="7" class="empty-state">No contribution files have been uploaded.</td></tr><?php endif; ?>
<?php foreach ($imports as $item): ?><tr><td><?= e($item['file_name']) ?></td><td><?= e(date('F Y', strtotime($item['contribution_month']))) ?></td><td><?= e(date('M j, Y H:i', strtotime($item['created_at']))) ?></td><td><?= number_format((int)$item['total_rows']) ?> total / <?= number_format((int)$item['valid_rows']) ?> valid</td><td><?= number_format((int)$item['error_rows']) ?></td><td><?= status_badge($item['status']) ?></td><td><a class="text-link" href="index.php?page=<?= $item['status'] === 'preview' ? 'import_preview' : 'import_summary' ?>&id=<?= (int)$item['id'] ?>">View</a><?php if ((int)$item['error_rows'] > 0): ?> · <a class="text-link" href="index.php?page=error_report&id=<?= (int)$item['id'] ?>">Error CSV</a><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
