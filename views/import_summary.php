<?php
$importId = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM csv_imports WHERE id = ?');
$stmt->execute([$importId]);
$batch = $stmt->fetch();
if (!$batch) {
    http_response_code(404);
    echo '<div class="notice-box">Import not found.</div>';
} else {
    $stmt = db()->prepare("SELECT COUNT(*) FROM csv_import_rows WHERE import_id = ? AND status = 'imported'");
    $stmt->execute([$importId]);
    $imported = (int)$stmt->fetchColumn();
    $stmt = db()->prepare("SELECT COALESCE(SUM(amount), 0) FROM csv_import_rows WHERE import_id = ? AND status = 'imported'");
    $stmt->execute([$importId]);
    $totalAmount = (float)$stmt->fetchColumn();
?>
<section class="page-heading"><p class="eyebrow">Import summary · <?= e(date('F Y', strtotime($batch['contribution_month']))) ?></p><h1><?= $batch['status'] === 'completed' ? 'Import completed' : 'Import awaiting confirmation' ?></h1><p><?= e($batch['file_name']) ?> · uploaded <?= e(date('M j, Y H:i', strtotime($batch['created_at']))) ?></p></section>
<section class="stats-grid"><article class="stat-card"><span>Imported records</span><strong><?= number_format($imported) ?></strong></article><article class="stat-card"><span>Contribution total</span><strong><?= e(money($totalAmount)) ?></strong></article><article class="stat-card"><span>Validation errors</span><strong><?= number_format((int)$batch['error_rows']) ?></strong></article></section>
<div class="button-row page-actions"><a class="button button-primary" href="index.php?page=contributions">Return to contributions</a><?php if ((int)$batch['error_rows'] > 0): ?><a class="button button-outline" href="index.php?page=error_report&id=<?= $importId ?>">Download error report</a><?php endif; ?></div>
<?php } ?>
