<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare(
        'SELECT u.id, u.email, u.role, u.active, m.id AS member_id, m.staff_id, m.full_name,
                m.phone, m.department, m.monthly_contribution, m.status AS member_status
         FROM users u LEFT JOIN members m ON m.user_id = u.id WHERE u.id = ?'
    );
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !(int)$user['active']) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please sign in to continue.');
        redirect('login');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Administrator access is required.');
    }
    return $user;
}

function redirect(string $page, array $query = []): never
{
    $query = ['page' => $page] + $query;
    header('Location: index.php?' . http_build_query($query));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function money(int|float|string|null $value): string
{
    return '₦' . number_format((float)$value, 2);
}

function audit(int $actorId, string $action, string $entity, ?int $entityId = null, array $details = []): void
{
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $actorId,
        $action,
        $entity,
        $entityId,
        json_encode($details, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function notify_member(PDO $pdo, int $memberId, string $title, string $body): void
{
    $stmt = $pdo->prepare('SELECT user_id FROM members WHERE id = ?');
    $stmt->execute([$memberId]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        throw new RuntimeException('Cannot notify a member whose account does not exist.');
    }
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, body) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $body]);
}

function savings_balance(int $memberId): float
{
    $stmt = db()->prepare(
        "SELECT COALESCE(SUM(CASE
            WHEN transaction_type = 'withdrawal' THEN -amount
            WHEN transaction_type IN ('contribution', 'adjustment') THEN amount
            ELSE 0 END), 0)
         FROM financial_ledger WHERE member_id = ? AND status = 'posted'"
    );
    $stmt->execute([$memberId]);
    return (float)$stmt->fetchColumn();
}

function outstanding_loan_balance(int $memberId): float
{
    $stmt = db()->prepare(
        "SELECT COALESCE((SELECT SUM(principal_amount) FROM loans
                         WHERE member_id = ? AND status IN ('approved', 'active'))
                        - (SELECT COALESCE(SUM(r.amount), 0) FROM loan_repayments r
                           JOIN loans l ON l.id = r.loan_id WHERE l.member_id = ?), 0)"
    );
    $stmt->execute([$memberId, $memberId]);
    return max(0, (float)$stmt->fetchColumn());
}

function status_badge(string $status): string
{
    $class = match (strtolower($status)) {
        'active', 'approved', 'completed', 'posted', 'settled' => 'success',
        'pending', 'preview' => 'warning',
        'rejected', 'failed', 'terminated', 'reversed' => 'danger',
        default => 'muted',
    };
    return '<span class="badge badge-' . $class . '">' . e(ucfirst($status)) . '</span>';
}
