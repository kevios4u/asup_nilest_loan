<?php
$announcements = db()->query(
    'SELECT a.*, u.email FROM announcements a JOIN users u ON u.id = a.author_id ORDER BY a.created_at DESC LIMIT 100'
)->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Administrator portal</p><h1>Announcements</h1><p>Publish updates for members and public website visitors.</p></div></div>
<section class="panel form-panel"><h2>Publish an announcement</h2><form method="post" class="form-stack"><?= csrf_field() ?><input type="hidden" name="action" value="announcement"><label>Title<input name="title" maxlength="190" required></label><label>Message<textarea name="body" rows="4" maxlength="10000" required></textarea></label><button class="button button-primary" type="submit">Publish announcement</button></form></section>
<section class="section-block"><div class="section-heading"><div><p class="eyebrow">Published updates</p><h2>Announcement history</h2></div></div><div class="announcement-grid"><?php if (!$announcements): ?><p class="empty-state">No announcements yet.</p><?php endif; ?><?php foreach ($announcements as $item): ?><article class="announcement-card"><time><?= e(date('M j, Y H:i', strtotime($item['created_at']))) ?></time><h3><?= e($item['title']) ?></h3><p><?= nl2br(e($item['body'])) ?></p><small class="muted">Published by <?= e($item['email']) ?></small></article><?php endforeach; ?></div></section>
