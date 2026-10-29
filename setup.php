<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';

$message = '';
$installed = false;
try {
    try {
        $pdo = db();
    } catch (PDOException $exception) {
        $isMissingDatabase = isset($exception->errorInfo[1]) && (int)$exception->errorInfo[1] === 1049;
        if (!$isMissingDatabase) {
            throw $exception;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', DB_NAME)) {
            throw new RuntimeException('The configured database name is invalid.');
        }
        $server = db_server();
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = db();
    }
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('The database schema file could not be read.');
    }
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        $pdo->exec($statement);
    }

    $table = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if ($table) {
        $installed = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
        verify_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $password !== $confirm) {
            $message = 'Use a valid email and a password of at least 12 characters; both passwords must match.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
            audit((int)$pdo->lastInsertId(), 'initial_admin_created', 'user');
            $installed = true;
        }
    }
} catch (PDOException $exception) {
    $cannotCreateDatabase = isset($exception->errorInfo[1]) && (int)$exception->errorInfo[1] === 1044;
    $message = $cannotCreateDatabase
        ? 'The MySQL account cannot create databases. In phpMyAdmin, create "' . DB_NAME
            . '" with the utf8mb4_unicode_ci collation, then reload this page.'
        : 'Cannot connect to MySQL or initialize the application tables. Start MySQL in XAMPP, then check the connection settings and database permissions in config.php.';
} catch (Throwable $exception) {
    $message = 'Setup could not continue. Check the schema file, database name, and MySQL account permissions.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install · <?= e(APP_NAME) ?></title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="manifest" href="assets/images/site.webmanifest">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="setup-page">
<main class="setup-card">
    <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/asup-nilest-loan-logo.png" alt=""><span class="brand-title">ASUP-NILEST<br>SAVING &amp; CREDIT SCHEME</span></a>
    <p class="eyebrow">First-time setup</p>
    <h1><?= $installed ? 'Setup complete' : 'Create your administrator' ?></h1>
    <?php if ($message !== ''): ?><div class="alert alert-error"><?= e($message) ?></div><?php endif; ?>
    <?php if ($installed): ?>
        <p>Your administrator account and application tables are ready. Sign in to continue.</p>
        <a class="button button-primary button-full" href="index.php?page=login">Go to sign in</a>
        <p class="small muted">For security, remove or restrict access to setup.php after installation.</p>
    <?php else: ?>
        <p>Enter the first administrator credentials. New member registrations will remain pending until approved.</p>
        <form method="post" class="form-stack">
            <?= csrf_field() ?>
            <label>Email address<input type="email" name="email" autocomplete="email" required></label>
            <label>Password<input type="password" name="password" minlength="12" autocomplete="new-password" required></label>
            <label>Confirm password<input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required></label>
            <button class="button button-primary button-full" type="submit">Install application</button>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
