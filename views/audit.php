<?php
$pageNumber = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$stmt = db()->prepare('SELECT a.*, u.email FROM audit_logs a JOIN users u ON u.id = a.actor_id ORDER BY a.created_at DESC, a.id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, ($pageNumber - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Administrative audit log</h1><p>A chronological record of application and financial actions.</p></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Date</th><th>Administrator / user</th><th>Action</th><th>Record</th><th>Details</th><th>IP address</th></tr></thead><tbody><?php if (!$logs): ?><tr><td colspan="6" class="empty-state">No audit events have been recorded.</td></tr><?php endif; ?><?php foreach ($logs as $log): ?><tr><td><?= e(date('M j, Y H:i:s', strtotime($log['created_at']))) ?></td><td><?= e($log['email']) ?></td><td><?= e(ucwords(str_replace('_', ' ', $log['action']))) ?></td><td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int)$log['entity_id'] : '' ?></td><td class="audit-details"><?= e($log['details'] ?? '—') ?></td><td><?= e($log['ip_address'] ?? '—') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php if (count($logs) === $perPage): ?><div class="page-actions"><a class="button button-outline" href="index.php?page=audit&p=<?= $pageNumber + 1 ?>">Older events →</a></div><?php endif; ?>
