<?php
$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>
<div class="dashboard-heading"><div><p class="eyebrow">Member portal</p><h1>Notifications</h1><p>Membership, request, and loan updates from the scheme.</p></div><?php if ($unreadNotifications > 0): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_notifications_read"><button class="button button-outline" type="submit">Mark all as read</button></form><?php endif; ?></div>
<section class="notification-list"><?php if (!$notifications): ?><article class="notification-item empty-state">You do not have any notifications yet.</article><?php endif; ?>
<?php foreach ($notifications as $notification): ?><article class="notification-item <?= $notification['read_at'] === null ? 'notification-unread' : '' ?>"><span class="notification-dot" aria-hidden="true"></span><div class="notification-copy"><a href="<?= e($notification['link']) ?>"><h2><?= e($notification['title']) ?></h2></a><p><?= e($notification['body']) ?></p><time><?= e(date('M j, Y · H:i', strtotime($notification['created_at']))) ?></time></div><?php if ($notification['read_at'] === null): ?><span class="badge badge-success">New</span><?php endif; ?></article><?php endforeach; ?>
</section>
