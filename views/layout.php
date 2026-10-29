<?php
$announcements = [];
$unreadNotifications = 0;
if ($user && $user['role'] === 'member') {
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $stmt->execute([$user['id']]);
    $unreadNotifications = (int)$stmt->fetchColumn();
}
if (in_array($page, ['home', 'about', 'membership', 'contact'], true)) {
    $announcements = db()->query('SELECT title, body, created_at FROM announcements WHERE published = 1 ORDER BY created_at DESC LIMIT 3')->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ASUP NILEST Savings & Credit Scheme member services, savings and loans.">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="manifest" href="assets/images/site.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/asup-nilest-loan-logo.png" alt=""><span class="brand-title">ASUP-NILEST<br>SAVING &amp; CREDIT SCHEME</span></a>
        <button class="nav-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-navigation"><ion-icon name="menu-outline" aria-hidden="true"></ion-icon></button>
        <nav class="main-nav" id="main-navigation">
            <?php if (!$user): ?>
                <a class="nav-link<?= $page === 'home' ? ' is-active' : '' ?>" href="index.php?page=home"<?= $page === 'home' ? ' aria-current="page"' : '' ?>><ion-icon name="home-outline" aria-hidden="true"></ion-icon>Home</a>
                <a class="nav-link<?= $page === 'about' ? ' is-active' : '' ?>" href="index.php?page=about"<?= $page === 'about' ? ' aria-current="page"' : '' ?>><ion-icon name="information-circle-outline" aria-hidden="true"></ion-icon>About</a>
                <a class="nav-link<?= $page === 'membership' ? ' is-active' : '' ?>" href="index.php?page=membership"<?= $page === 'membership' ? ' aria-current="page"' : '' ?>><ion-icon name="people-outline" aria-hidden="true"></ion-icon>Membership</a>
                <a class="nav-link<?= $page === 'contact' ? ' is-active' : '' ?>" href="index.php?page=contact"<?= $page === 'contact' ? ' aria-current="page"' : '' ?>><ion-icon name="help-circle-outline" aria-hidden="true"></ion-icon>Help</a>
                <a class="button button-outline button-small" href="index.php?page=login"><ion-icon name="log-in-outline" aria-hidden="true"></ion-icon>Sign in</a>
                <a class="button button-primary button-small" href="index.php?page=register"><ion-icon name="person-add-outline" aria-hidden="true"></ion-icon>Join the scheme</a>
            <?php elseif ($user['role'] === 'admin'): ?>
                <a class="nav-link<?= $page === 'dashboard' ? ' is-active' : '' ?>" href="index.php?page=dashboard"<?= $page === 'dashboard' ? ' aria-current="page"' : '' ?>><ion-icon name="speedometer-outline" aria-hidden="true"></ion-icon>Overview</a>
                <a class="nav-link<?= $page === 'members' ? ' is-active' : '' ?>" href="index.php?page=members"<?= $page === 'members' ? ' aria-current="page"' : '' ?>><ion-icon name="people-outline" aria-hidden="true"></ion-icon>Members</a>
                <a class="nav-link<?= $page === 'contributions' ? ' is-active' : '' ?>" href="index.php?page=contributions"<?= $page === 'contributions' ? ' aria-current="page"' : '' ?>><ion-icon name="cash-outline" aria-hidden="true"></ion-icon>Contributions</a>
                <a class="nav-link<?= $page === 'requests' ? ' is-active' : '' ?>" href="index.php?page=requests"<?= $page === 'requests' ? ' aria-current="page"' : '' ?>><ion-icon name="git-pull-request-outline" aria-hidden="true"></ion-icon>Requests</a>
                <a class="nav-link<?= $page === 'loans' ? ' is-active' : '' ?>" href="index.php?page=loans"<?= $page === 'loans' ? ' aria-current="page"' : '' ?>><ion-icon name="wallet-outline" aria-hidden="true"></ion-icon>Loans</a>
                <a class="nav-link<?= $page === 'reports' ? ' is-active' : '' ?>" href="index.php?page=reports"<?= $page === 'reports' ? ' aria-current="page"' : '' ?>><ion-icon name="bar-chart-outline" aria-hidden="true"></ion-icon>Reports</a>
                <a class="nav-link<?= $page === 'audit' ? ' is-active' : '' ?>" href="index.php?page=audit"<?= $page === 'audit' ? ' aria-current="page"' : '' ?>><ion-icon name="document-text-outline" aria-hidden="true"></ion-icon>Audit</a>
                <form method="post" class="nav-logout"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button type="submit"><ion-icon name="log-out-outline" aria-hidden="true"></ion-icon>Sign out</button></form>
            <?php else: ?>
                <a class="nav-link<?= $page === 'dashboard' ? ' is-active' : '' ?>" href="index.php?page=dashboard"<?= $page === 'dashboard' ? ' aria-current="page"' : '' ?>><ion-icon name="grid-outline" aria-hidden="true"></ion-icon>Dashboard</a>
                <a class="nav-link<?= $page === 'notifications' ? ' is-active' : '' ?>" href="index.php?page=notifications"<?= $page === 'notifications' ? ' aria-current="page"' : '' ?>><ion-icon name="notifications-outline" aria-hidden="true"></ion-icon>Notifications<?php if ($unreadNotifications): ?> <span class="nav-count"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span><?php endif; ?></a>
                <?php if (in_array($user['member_status'], ['active', 'terminated'], true)): ?>
                    <a class="nav-link<?= $page === 'statement' ? ' is-active' : '' ?>" href="index.php?page=statement"<?= $page === 'statement' ? ' aria-current="page"' : '' ?>><ion-icon name="card-outline" aria-hidden="true"></ion-icon>Savings</a>
                    <a class="nav-link<?= $page === 'requests' ? ' is-active' : '' ?>" href="index.php?page=requests"<?= $page === 'requests' ? ' aria-current="page"' : '' ?>><ion-icon name="git-pull-request-outline" aria-hidden="true"></ion-icon>Requests</a>
                    <a class="nav-link<?= $page === 'loans' ? ' is-active' : '' ?>" href="index.php?page=loans"<?= $page === 'loans' ? ' aria-current="page"' : '' ?>><ion-icon name="wallet-outline" aria-hidden="true"></ion-icon>Loans</a>
                <?php endif; ?>
                <?php if ($user['member_status'] === 'active'): ?>
                    <a class="nav-link<?= $page === 'profile' ? ' is-active' : '' ?>" href="index.php?page=profile"<?= $page === 'profile' ? ' aria-current="page"' : '' ?>><ion-icon name="person-circle-outline" aria-hidden="true"></ion-icon>Profile</a>
                <?php endif; ?>
                <form method="post" class="nav-logout"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button type="submit"><ion-icon name="log-out-outline" aria-hidden="true"></ion-icon>Sign out</button></form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <?php
    $viewFile = __DIR__ . '/' . $page . '.php';
    if (is_file($viewFile)) {
        require $viewFile;
    } else {
        require __DIR__ . '/home.php';
    }
    ?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div><a class="brand brand-light" href="index.php"><img class="brand-logo" src="assets/images/asup-nilest-loan-logo.png" alt=""><span class="brand-title">ASUP-NILEST<br>SAVING &amp; CREDIT SCHEME</span></a><p>Building a stronger culture of saving, together.</p></div>
        <div class="footer-links"><a href="index.php?page=about">About the scheme</a><a href="index.php?page=membership">Membership</a><a href="index.php?page=contact">Contact & help</a></div>
        <small>© <?= date('Y') ?> ASUP NILEST Savings & Credit Scheme</small>
    </div>
</footer>
</body>
</html>
