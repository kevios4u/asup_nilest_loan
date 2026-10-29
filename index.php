<?php
declare(strict_types=1);

require_once __DIR__ . '/app/helpers.php';

$page = (string)($_GET['page'] ?? 'home');
try {
    db();
    $user = current_user();
} catch (PDOException $exception) {
    header('Location: setup.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    switch ($action) {
        case 'register':
            $name = trim((string)($_POST['full_name'] ?? ''));
            $staffId = trim((string)($_POST['staff_id'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $department = trim((string)($_POST['department'] ?? ''));
            $contribution = filter_var($_POST['monthly_contribution'] ?? null, FILTER_VALIDATE_FLOAT);
            $password = (string)($_POST['password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            if ($name === '' || $staffId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === ''
                || $contribution === false || $contribution <= 0 || strlen($password) < 10) {
                flash('error', 'Complete all fields with valid details. Passwords must be at least 10 characters.');
                redirect('register');
            }
            if ($password !== $confirmPassword) {
                flash('error', 'Passwords do not match.');
                redirect('register');
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
                $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare(
                    'INSERT INTO members (user_id, staff_id, full_name, phone, department, monthly_contribution)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$userId, $staffId, $name, $phone, $department, $contribution]);
                $memberId = (int)$pdo->lastInsertId();
                audit($userId, 'membership_application_submitted', 'member', $memberId);
                $pdo->commit();
                flash('success', 'Your application has been submitted. An administrator must approve it before you can use member services.');
                redirect('login');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($exception->getCode() === '23000') {
                    flash('error', 'That email address or staff ID is already registered.');
                    redirect('register');
                }
                throw $exception;
            }

        case 'login':
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $stmt = db()->prepare('SELECT id, password_hash, active FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $account = $stmt->fetch();
            if (!$account || !(int)$account['active'] || !password_verify((string)($_POST['password'] ?? ''), $account['password_hash'])) {
                flash('error', 'The email or password is incorrect, or this account is inactive.');
                redirect('login');
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            redirect('dashboard');

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'You have been signed out.');
            redirect('home');

        case 'contact_submit':
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $subject = trim((string)($_POST['subject'] ?? ''));
            $message = trim((string)($_POST['message'] ?? ''));
            if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '' || strlen($message) < 10) {
                flash('error', 'Please complete all required fields with a valid email and a clear message.');
                redirect('contact');
            }
            $stmt = db()->prepare(
                'INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$fullName, $email, $phone !== '' ? $phone : null, $subject, $message]);
            $contactId = (int)db()->lastInsertId();
            $actorId = $_SESSION['user_id'] ?? null;
            if ($actorId !== null && (int)$actorId > 0) {
                audit((int)$actorId, 'contact_message_submitted', 'contact_message', $contactId, ['subject' => $subject]);
            }
            flash('success', 'Your message has been sent successfully. Our support team will get back to you soon.');
            redirect('contact');

        case 'update_profile':
            $member = require_login();
            if ($member['role'] !== 'member' || $member['member_status'] !== 'active') {
                http_response_code(403);
                exit('An active member account is required.');
            }
            $name = trim((string)($_POST['full_name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $department = trim((string)($_POST['department'] ?? ''));
            if ($name === '' || $phone === '') {
                flash('error', 'Name and phone number are required.');
                redirect('profile');
            }
            $stmt = db()->prepare('UPDATE members SET full_name = ?, phone = ?, department = ? WHERE id = ?');
            $stmt->execute([$name, $phone, $department, $member['member_id']]);
            audit((int)$member['id'], 'profile_updated', 'member', (int)$member['member_id']);
            flash('success', 'Your profile has been updated.');
            redirect('profile');

        case 'change_password':
            $member = require_login();
            $currentPassword = (string)($_POST['current_password'] ?? '');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$member['id']]);
            if (!password_verify($currentPassword, (string)$stmt->fetchColumn())) {
                flash('error', 'Your current password is incorrect.');
                redirect('profile');
            }
            if (strlen($newPassword) < 10 || $newPassword !== (string)($_POST['confirm_password'] ?? '')) {
                flash('error', 'New passwords must match and contain at least 10 characters.');
                redirect('profile');
            }
            $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $member['id']]);
            audit((int)$member['id'], 'password_changed', 'user', (int)$member['id']);
            flash('success', 'Your password has been changed.');
            redirect('profile');

        case 'member_request':
            $member = require_login();
            if ($member['role'] !== 'member' || $member['member_status'] !== 'active') {
                http_response_code(403);
                exit('An active member account is required.');
            }
            $type = (string)($_POST['request_type'] ?? '');
            $allowed = ['contribution_increase', 'contribution_reduction', 'withdrawal', 'termination'];
            $reason = trim((string)($_POST['reason'] ?? ''));
            $amount = null;
            $proposed = null;
            if (!in_array($type, $allowed, true) || $reason === '') {
                flash('error', 'Choose a valid request type and provide a reason.');
                redirect('requests');
            }
            if (in_array($type, ['contribution_increase', 'contribution_reduction'], true)) {
                $proposed = filter_var($_POST['proposed_contribution'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($proposed === false || $proposed <= 0
                    || ($type === 'contribution_increase' && $proposed <= (float)$member['monthly_contribution'])
                    || ($type === 'contribution_reduction' && $proposed >= (float)$member['monthly_contribution'])) {
                    flash('error', 'Enter a valid contribution amount that reflects the requested increase or reduction.');
                    redirect('requests');
                }
            } elseif ($type === 'withdrawal') {
                $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($amount === false || $amount <= 0 || savings_balance((int)$member['member_id']) - $amount < 1000) {
                    flash('error', 'The withdrawal must be positive and leave at least ₦1,000 in your savings.');
                    redirect('requests');
                }
            }
            $stmt = db()->prepare(
                'INSERT INTO member_requests (member_id, request_type, amount, proposed_contribution, reason)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$member['member_id'], $type, $amount, $proposed, $reason]);
            $requestId = (int)db()->lastInsertId();
            audit((int)$member['id'], 'member_request_submitted', 'member_request', $requestId, ['type' => $type]);
            flash('success', 'Your request has been submitted for administrator review.');
            redirect('requests');

        case 'loan_application':
            $member = require_login();
            if ($member['role'] !== 'member' || $member['member_status'] !== 'active') {
                http_response_code(403);
                exit('An active member account is required.');
            }
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $purpose = trim((string)($_POST['purpose'] ?? ''));
            if ($amount === false || $amount <= 0 || $purpose === '') {
                flash('error', 'Enter a positive loan amount and explain its purpose.');
                redirect('loans');
            }
            $stmt = db()->prepare('INSERT INTO loans (member_id, requested_amount, purpose) VALUES (?, ?, ?)');
            $stmt->execute([$member['member_id'], $amount, $purpose]);
            $loanId = (int)db()->lastInsertId();
            audit((int)$member['id'], 'loan_application_submitted', 'loan', $loanId);
            flash('success', 'Your loan application has been submitted.');
            redirect('loans');

        case 'approve_member':
            $admin = require_admin();
            $memberId = (int)($_POST['member_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            if (!in_array($decision, ['active', 'rejected'], true)) {
                flash('error', 'Invalid membership decision.');
                redirect('members');
            }
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE id = ? AND status = 'pending'");
            $stmt->execute([$decision, $memberId]);
            if ($stmt->rowCount()) {
                notify_member(
                    $pdo,
                    $memberId,
                    $decision === 'active' ? 'Membership approved' : 'Membership application update',
                    $decision === 'active'
                        ? 'Your membership application has been approved. Sign in to access your member services.'
                        : 'Your membership application was not approved. Contact the scheme administrator if you need assistance.'
                );
                audit((int)$admin['id'], 'membership_' . $decision, 'member', $memberId);
                $pdo->commit();
                flash('success', 'Membership application ' . $decision . '.');
            } else {
                $pdo->rollBack();
                flash('error', 'This application is no longer pending.');
            }
            redirect('members');

        case 'create_member':
            $admin = require_admin();
            $name = trim((string)($_POST['full_name'] ?? ''));
            $staffId = trim((string)($_POST['staff_id'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $department = trim((string)($_POST['department'] ?? ''));
            $contribution = filter_var($_POST['monthly_contribution'] ?? null, FILTER_VALIDATE_FLOAT);
            $password = (string)($_POST['password'] ?? '');
            $status = (string)($_POST['status'] ?? 'pending');
            if ($name === '' || $staffId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === ''
                || $contribution === false || $contribution <= 0 || strlen($password) < 10
                || !in_array($status, ['pending', 'active'], true)) {
                flash('error', 'Enter valid member details. An initial password must contain at least 10 characters.');
                redirect('members');
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
                $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare(
                    'INSERT INTO members (user_id, staff_id, full_name, phone, department, monthly_contribution, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$userId, $staffId, $name, $phone, $department, $contribution, $status]);
                $memberId = (int)$pdo->lastInsertId();
                audit((int)$admin['id'], 'member_added_individually', 'member', $memberId, ['status' => $status]);
                $pdo->commit();
                flash('success', 'Member account created successfully.');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($exception->getCode() === '23000') {
                    flash('error', 'That email address or staff ID is already registered.');
                    redirect('members');
                }
                throw $exception;
            }
            redirect('members');

        case 'update_member':
            $admin = require_admin();
            $memberId = (int)($_POST['member_id'] ?? 0);
            $name = trim((string)($_POST['full_name'] ?? ''));
            $staffId = trim((string)($_POST['staff_id'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $department = trim((string)($_POST['department'] ?? ''));
            $contribution = filter_var($_POST['monthly_contribution'] ?? null, FILTER_VALIDATE_FLOAT);
            $status = (string)($_POST['status'] ?? '');
            if ($memberId <= 0 || $name === '' || $staffId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === ''
                || $contribution === false || $contribution <= 0
                || !in_array($status, ['pending', 'active', 'rejected', 'terminated'], true)) {
                flash('error', 'Enter valid member details before saving changes.');
                redirect('members', ['edit' => $memberId]);
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT user_id FROM members WHERE id = ? FOR UPDATE');
                $stmt->execute([$memberId]);
                $memberUserId = (int)$stmt->fetchColumn();
                if ($memberUserId <= 0) {
                    $pdo->rollBack();
                    flash('error', 'The selected member could not be found.');
                    redirect('members');
                }
                $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $memberUserId]);
                $pdo->prepare(
                    'UPDATE members SET staff_id = ?, full_name = ?, phone = ?, department = ?, monthly_contribution = ?, status = ? WHERE id = ?'
                )->execute([$staffId, $name, $phone, $department, $contribution, $status, $memberId]);
                audit((int)$admin['id'], 'member_details_updated', 'member', $memberId, ['status' => $status]);
                $pdo->commit();
                flash('success', 'Member details updated successfully.');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($exception->getCode() === '23000') {
                    flash('error', 'That email address or staff ID is already assigned to another member.');
                    redirect('members', ['edit' => $memberId]);
                }
                throw $exception;
            }
            redirect('members');

        case 'bulk_members':
            $admin = require_admin();
            $file = $_FILES['members_csv'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])
                || strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'csv') {
                flash('error', 'Choose a valid CSV roster file.');
                redirect('members');
            }
            $handle = fopen($file['tmp_name'], 'rb');
            if ($handle === false) {
                flash('error', 'The member roster CSV could not be read.');
                redirect('members');
            }
            $header = fgetcsv($handle);
            $columnMap = is_array($header)
                ? array_flip(array_map(static fn($value): string => strtolower(trim((string)$value)), $header))
                : [];
            $requiredColumns = ['staff_id', 'full_name', 'email', 'phone', 'department', 'monthly_contribution', 'password'];
            if (array_diff($requiredColumns, array_keys($columnMap))) {
                fclose($handle);
                flash('error', 'The roster must include: ' . implode(', ', $requiredColumns) . '.');
                redirect('members');
            }
            $pdo = db();
            $pdo->beginTransaction();
            $seenEmails = [];
            $seenStaffIds = [];
            $created = 0;
            $errors = [];
            $line = 1;
            $lookup = $pdo->prepare('SELECT u.id FROM users u JOIN members m ON m.user_id = u.id WHERE u.email = ? OR m.staff_id = ?');
            $createUser = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
            $createMember = $pdo->prepare(
                'INSERT INTO members (user_id, staff_id, full_name, phone, department, monthly_contribution)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            while (($csvRow = fgetcsv($handle)) !== false) {
                $line++;
                if (count($csvRow) === 1 && trim((string)$csvRow[0]) === '') continue;
                $value = static fn(string $key): string => trim((string)($csvRow[$columnMap[$key]] ?? ''));
                $staffId = $value('staff_id');
                $fullName = $value('full_name');
                $email = strtolower($value('email'));
                $phone = $value('phone');
                $department = $value('department');
                $monthlyContribution = filter_var(str_replace([',', '₦', ' '], '', $value('monthly_contribution')), FILTER_VALIDATE_FLOAT);
                $password = $value('password');
                $error = '';
                if ($staffId === '' || $fullName === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Missing or invalid staff ID, full name, email, or phone.';
                } elseif ($monthlyContribution === false || $monthlyContribution <= 0) {
                    $error = 'Monthly contribution must be a positive amount.';
                } elseif (strlen($password) < 10) {
                    $error = 'Temporary password must be at least 10 characters.';
                } elseif (isset($seenEmails[$email]) || isset($seenStaffIds[$staffId])) {
                    $error = 'Duplicate email or staff ID in this file.';
                } else {
                    $seenEmails[$email] = true;
                    $seenStaffIds[$staffId] = true;
                    $lookup->execute([$email, $staffId]);
                    if ($lookup->fetchColumn()) $error = 'Email or staff ID is already registered.';
                }
                if ($error !== '') {
                    $errors[] = 'Row ' . $line . ': ' . $error;
                    continue;
                }
                $createUser->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                $memberUserId = (int)$pdo->lastInsertId();
                $createMember->execute([$memberUserId, $staffId, $fullName, $phone, $department, $monthlyContribution]);
                $memberId = (int)$pdo->lastInsertId();
                audit((int)$admin['id'], 'member_bulk_registered', 'member', $memberId);
                $created++;
            }
            fclose($handle);
            audit((int)$admin['id'], 'member_roster_imported', 'member_roster', null, ['created' => $created, 'errors' => count($errors)]);
            $pdo->commit();
            if ($errors) {
                flash('error', $created . ' members registered. ' . count($errors) . ' rows skipped. ' . implode(' ', array_slice($errors, 0, 8)));
            } else {
                flash('success', $created . ' member applications registered and are pending approval.');
            }
            redirect('members');

        case 'review_request':
            $admin = require_admin();
            $requestId = (int)($_POST['request_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            $notes = trim((string)($_POST['reviewer_notes'] ?? ''));
            if (!in_array($decision, ['approved', 'rejected'], true)) {
                flash('error', 'Invalid request decision.');
                redirect('requests');
            }
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM member_requests WHERE id = ? FOR UPDATE');
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            if (!$request || $request['status'] !== 'pending') {
                $pdo->rollBack();
                flash('error', 'This request is no longer pending.');
                redirect('requests');
            }
            $memberId = (int)$request['member_id'];
            if ($decision === 'approved') {
                if ($request['request_type'] === 'withdrawal') {
                    $paymentReference = trim((string)($_POST['payment_reference'] ?? ''));
                    if ($paymentReference === '') {
                        $pdo->rollBack();
                        flash('error', 'Enter the payment reference before approving a withdrawal.');
                        redirect('requests');
                    }
                    $pdo->prepare('SELECT id FROM members WHERE id = ? FOR UPDATE')->execute([$memberId]);
                    $balance = savings_balance($memberId);
                    if ((float)$request['amount'] <= 0 || $balance - (float)$request['amount'] < 1000) {
                        $pdo->rollBack();
                        flash('error', 'Approval denied: the withdrawal must leave at least ₦1,000 in the member savings.');
                        redirect('requests');
                    }
                    $pdo->prepare(
                        "INSERT INTO financial_ledger
                         (transaction_ref, member_id, transaction_type, amount, authorization_details, payment_reference, authorized_by)
                         VALUES (?, ?, 'withdrawal', ?, ?, ?, ?)"
                    )->execute([
                        bin2hex(random_bytes(12)), $memberId, $request['amount'],
                        'Approved member withdrawal request #' . $requestId, $paymentReference,
                        $admin['id'],
                    ]);
                } elseif (in_array($request['request_type'], ['contribution_increase', 'contribution_reduction'], true)) {
                    $pdo->prepare('UPDATE members SET monthly_contribution = ? WHERE id = ?')
                        ->execute([$request['proposed_contribution'], $memberId]);
                } elseif ($request['request_type'] === 'termination') {
                    $pdo->prepare('SELECT id FROM members WHERE id = ? FOR UPDATE')->execute([$memberId]);
                    if (outstanding_loan_balance($memberId) > 0.005) {
                        $pdo->rollBack();
                        flash('error', 'Membership termination cannot be approved while a loan balance is outstanding.');
                        redirect('requests');
                    }
                    $balance = savings_balance($memberId);
                    if ($balance > 0) {
                        $pdo->prepare(
                            "INSERT INTO financial_ledger
                             (transaction_ref, member_id, transaction_type, amount, authorization_details, payment_reference, authorized_by)
                             VALUES (?, ?, 'withdrawal', ?, ?, ?, ?)"
                        )->execute([
                            bin2hex(random_bytes(12)), $memberId, $balance,
                            'Final payout on membership termination (Request #' . $requestId . ')', null,
                            $admin['id'],
                        ]);
                    }
                    $pdo->prepare("UPDATE members SET status = 'terminated' WHERE id = ?")->execute([$memberId]);
                }
            }
            $pdo->prepare(
                'UPDATE member_requests SET status = ?, reviewer_id = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?'
            )->execute([$decision, $admin['id'], $notes, $requestId]);
            notify_member(
                $pdo,
                $memberId,
                'Request ' . $decision,
                'Your ' . str_replace('_', ' ', $request['request_type']) . ' request has been ' . $decision . '.'
                    . ($notes !== '' ? ' Administrator note: ' . $notes : '')
            );
            audit((int)$admin['id'], 'member_request_' . $decision, 'member_request', $requestId, ['type' => $request['request_type']]);
            $pdo->commit();
            flash('success', 'Member request ' . $decision . '.');
            redirect('requests');

        case 'import_loan_deductions':
            $admin = require_admin();
            $file = $_FILES['loan_deductions_csv'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])
                || strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'csv') {
                flash('error', 'Choose a valid loan deductions CSV file.');
                redirect('loans');
            }
            $handle = fopen($file['tmp_name'], 'rb');
            if ($handle === false) { flash('error', 'The loan deductions CSV could not be read.'); redirect('loans'); }
            $header = fgetcsv($handle);
            $columnMap = is_array($header) ? array_flip(array_map(static fn($value): string => strtolower(trim((string)$value)), $header)) : [];
            $requiredColumns = ['staff_id', 'loan_reference', 'amount', 'payment_reference'];
            if (array_diff($requiredColumns, array_keys($columnMap))) {
                fclose($handle);
                flash('error', 'The CSV must include: ' . implode(', ', $requiredColumns) . '.');
                redirect('loans');
            }
            $pdo = db();
            $pdo->beginTransaction();
            $line = 1; $errors = []; $rows = []; $seenReferences = []; $remainingByLoan = [];
            $findLoan = $pdo->prepare(
                "SELECT l.*, m.id AS member_id, COALESCE((SELECT SUM(r.amount) FROM loan_repayments r WHERE r.loan_id = l.id), 0) AS repaid
                 FROM loans l JOIN members m ON m.id = l.member_id
                 WHERE m.staff_id = ? AND l.id = ? AND l.status IN ('approved', 'active') FOR UPDATE"
            );
            $referenceExists = $pdo->prepare('SELECT COUNT(*) FROM loan_repayments WHERE payment_reference = ?');
            while (($csvRow = fgetcsv($handle)) !== false) {
                $line++;
                if (count($csvRow) === 1 && trim((string)$csvRow[0]) === '') continue;
                $value = static fn(string $key): string => trim((string)($csvRow[$columnMap[$key]] ?? ''));
                $staffId = $value('staff_id'); $loanReference = $value('loan_reference'); $paymentReference = $value('payment_reference');
                $amount = filter_var(str_replace([',', '₦', ' '], '', $value('amount')), FILTER_VALIDATE_FLOAT);
                $loanId = preg_match('/^(?:LOAN-)?(\d+)$/i', $loanReference, $matches) ? (int)$matches[1] : 0;
                $error = '';
                if ($staffId === '' || $loanId <= 0 || $paymentReference === '' || $amount === false || $amount <= 0) {
                    $error = 'Staff ID, loan reference, positive amount, and payment reference are required.';
                } elseif (isset($seenReferences[$paymentReference])) {
                    $error = 'Duplicate payment reference in this file.';
                } else {
                    $referenceExists->execute([$paymentReference]);
                    if ((int)$referenceExists->fetchColumn() > 0) {
                        $error = 'Payment reference has already been recorded.';
                    } else {
                        $findLoan->execute([$staffId, $loanId]);
                        $loan = $findLoan->fetch();
                        if (!$loan) {
                            $error = 'No approved or active loan matches this staff ID and loan reference.';
                        } else {
                            $remaining = $remainingByLoan[$loanId] ?? max(0, (float)$loan['principal_amount'] - (float)$loan['repaid']);
                            if ($amount > $remaining + 0.004) {
                                $error = 'Deduction exceeds the remaining loan balance of ' . money($remaining) . '.';
                            } else {
                                $remainingByLoan[$loanId] = $remaining - $amount;
                                $rows[] = ['loan_id' => $loanId, 'member_id' => (int)$loan['member_id'], 'amount' => $amount, 'reference' => $paymentReference];
                                $seenReferences[$paymentReference] = true;
                            }
                        }
                    }
                }
                if ($error !== '') $errors[] = 'Row ' . $line . ': ' . $error;
            }
            fclose($handle);
            if (!$rows) $errors[] = 'No valid deduction rows were found.';
            if ($errors) {
                $pdo->rollBack();
                flash('error', 'Loan deductions were not imported. ' . implode(' ', array_slice($errors, 0, 8)));
                redirect('loans');
            }
            $insertRepayment = $pdo->prepare('INSERT INTO loan_repayments (loan_id, amount, payment_reference, received_by) VALUES (?, ?, ?, ?)');
            $insertLedger = $pdo->prepare(
                "INSERT INTO financial_ledger (transaction_ref, member_id, transaction_type, amount, authorization_details, payment_reference, authorized_by)
                 VALUES (?, ?, 'loan_repayment', ?, ?, ?, ?)"
            );
            $updateLoan = $pdo->prepare('UPDATE loans SET status = ? WHERE id = ?');
            foreach ($rows as $row) {
                $insertRepayment->execute([$row['loan_id'], $row['amount'], $row['reference'], $admin['id']]);
                $insertLedger->execute([bin2hex(random_bytes(12)), $row['member_id'], $row['amount'], 'CSV loan deduction for loan #' . $row['loan_id'], $row['reference'], $admin['id']]);
                $balance = $remainingByLoan[$row['loan_id']];
                $updateLoan->execute([$balance < 0.005 ? 'settled' : 'active', $row['loan_id']]);
            }
            audit((int)$admin['id'], 'loan_deductions_csv_imported', 'loan_repayment', null, ['rows' => count($rows), 'file' => $file['name']]);
            $pdo->commit();
            flash('success', count($rows) . ' loan deduction' . (count($rows) === 1 ? '' : 's') . ' imported successfully.');
            redirect('loans');

        case 'review_loan':
            $admin = require_admin();
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            $approvedAmount = filter_var($_POST['approved_amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM loans WHERE id = ? FOR UPDATE');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();
            if (!$loan || $loan['status'] !== 'pending') {
                $pdo->rollBack();
                flash('error', 'This loan is no longer pending.');
                redirect('loans');
            }
            $memberStatusQuery = $pdo->prepare('SELECT status FROM members WHERE id = ? FOR UPDATE');
            $memberStatusQuery->execute([$loan['member_id']]);
            $memberStatus = $memberStatusQuery->fetchColumn();
            $paymentReference = trim((string)($_POST['payment_reference'] ?? ''));
            if ($decision === 'approved' && $paymentReference !== '' && $approvedAmount !== false && $approvedAmount > 0
                && $approvedAmount <= (float)$loan['requested_amount'] && $memberStatus === 'active') {
                $pdo->prepare(
                    "UPDATE loans SET status = 'approved', principal_amount = ?, approved_by = ?, approved_at = NOW()
                     WHERE id = ?"
                )->execute([$approvedAmount, $admin['id'], $loanId]);
                $pdo->prepare(
                    "INSERT INTO financial_ledger
                     (transaction_ref, member_id, transaction_type, amount, authorization_details, payment_reference, authorized_by)
                     VALUES (?, ?, 'loan_disbursement', ?, ?, ?, ?)"
                )->execute([
                    bin2hex(random_bytes(12)), $loan['member_id'], $approvedAmount,
                    'Loan #' . $loanId . ' approved and disbursed', $paymentReference, $admin['id'],
                ]);
            } elseif ($decision === 'rejected') {
                $pdo->prepare("UPDATE loans SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?")
                    ->execute([$admin['id'], $loanId]);
            } else {
                $pdo->rollBack();
                flash('error', $decision === 'approved' && $memberStatus !== 'active'
                    ? 'A loan cannot be approved for an inactive member.'
                    : 'Enter a valid decision, payment reference, and approved amount (no greater than requested).');
                redirect('loans');
            }
            notify_member(
                $pdo,
                (int)$loan['member_id'],
                'Loan application ' . $decision,
                $decision === 'approved'
                    ? 'Your loan application was approved for ' . money($approvedAmount) . '.'
                    : 'Your loan application was not approved.'
            );
            audit((int)$admin['id'], 'loan_' . $decision, 'loan', $loanId);
            $pdo->commit();
            flash('success', 'Loan application ' . $decision . '.');
            redirect('loans');

        case 'record_repayment':
            $admin = require_admin();
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            $reference = trim((string)($_POST['payment_reference'] ?? ''));
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND status IN ('approved', 'active') FOR UPDATE");
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();
            if (!$loan || $amount === false || $amount <= 0 || $reference === '') {
                $pdo->rollBack();
                flash('error', 'Select an active loan and enter a positive repayment amount with a payment reference.');
                redirect('loans');
            }
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE loan_id = ?');
            $stmt->execute([$loanId]);
            $outstanding = (float)$loan['principal_amount'] - (float)$stmt->fetchColumn();
            if ($amount > $outstanding) {
                $pdo->rollBack();
                flash('error', 'The repayment cannot exceed the loan balance of ' . money($outstanding) . '.');
                redirect('loans');
            }
            $pdo->prepare('INSERT INTO loan_repayments (loan_id, amount, payment_reference, received_by) VALUES (?, ?, ?, ?)')
                ->execute([$loanId, $amount, $reference, $admin['id']]);
            $pdo->prepare(
                "INSERT INTO financial_ledger
                 (transaction_ref, member_id, transaction_type, amount, authorization_details, payment_reference, authorized_by)
                 VALUES (?, ?, 'loan_repayment', ?, ?, ?, ?)"
            )->execute([
                bin2hex(random_bytes(12)), $loan['member_id'], $amount, 'Repayment for loan #' . $loanId,
                $reference, $admin['id'],
            ]);
            $newBalance = $outstanding - $amount;
            $pdo->prepare('UPDATE loans SET status = ? WHERE id = ?')->execute([$newBalance < 0.005 ? 'settled' : 'active', $loanId]);
            notify_member(
                $pdo,
                (int)$loan['member_id'],
                'Loan repayment recorded',
                money($amount) . ' has been recorded against your loan. Remaining balance: ' . money($newBalance) . '.'
            );
            audit((int)$admin['id'], 'loan_repayment_recorded', 'loan', $loanId, ['amount' => $amount, 'reference' => $reference]);
            $pdo->commit();
            flash('success', 'Loan repayment recorded.');
            redirect('loans');

        case 'announcement':
            $admin = require_admin();
            $title = trim((string)($_POST['title'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            if ($title === '' || $body === '') {
                flash('error', 'Announcement title and message are required.');
                redirect('announcements');
            }
            $stmt = db()->prepare('INSERT INTO announcements (title, body, author_id) VALUES (?, ?, ?)');
            $stmt->execute([$title, $body, $admin['id']]);
            $announcementId = (int)db()->lastInsertId();
            audit((int)$admin['id'], 'announcement_published', 'announcement', $announcementId);
            flash('success', 'Announcement published.');
            redirect('announcements');

        case 'mark_notifications_read':
            $member = require_login();
            if ($member['role'] !== 'member') {
                http_response_code(403);
                exit('Member access is required.');
            }
            $stmt = db()->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL');
            $stmt->execute([$member['id']]);
            redirect('notifications');

        case 'import_upload':
            $admin = require_admin();
            $month = trim((string)($_POST['contribution_month'] ?? ''));
            $staffColumn = (int)($_POST['staff_column'] ?? 0);
            $amountColumn = (int)($_POST['amount_column'] ?? 1);
            $file = $_FILES['csv_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])
                || strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'csv'
                || !preg_match('/^\d{4}-\d{2}$/', $month) || $staffColumn < 0 || $amountColumn < 0
                || $staffColumn === $amountColumn) {
                flash('error', 'Choose a CSV file, valid contribution month, and distinct column positions.');
                redirect('contributions');
            }
            $monthDate = $month . '-01';
            $monthCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $monthDate);
            if (!$monthCheck || $monthCheck->format('Y-m-d') !== $monthDate) {
                flash('error', 'Choose a valid contribution month.');
                redirect('contributions');
            }
            $handle = fopen($file['tmp_name'], 'rb');
            if ($handle === false) {
                flash('error', 'The uploaded CSV could not be read.');
                redirect('contributions');
            }
            $header = fgetcsv($handle);
            if (!is_array($header) || !isset($header[$staffColumn], $header[$amountColumn])) {
                fclose($handle);
                flash('error', 'The selected staff ID or amount column does not exist in the CSV header.');
                redirect('contributions');
            }
            $rows = [];
            $seenStaffIds = [];
            $line = 1;
            while (($csvRow = fgetcsv($handle)) !== false) {
                $line++;
                if ($csvRow === [null] || (count($csvRow) === 1 && trim((string)$csvRow[0]) === '')) {
                    continue;
                }
                $staff = trim((string)($csvRow[$staffColumn] ?? ''));
                $rawAmount = trim((string)($csvRow[$amountColumn] ?? ''));
                $normalized = str_replace([',', '₦', ' '], '', $rawAmount);
                $amount = filter_var($normalized, FILTER_VALIDATE_FLOAT);
                $message = '';
                $memberId = null;
                if ($staff === '') {
                    $message = 'Missing staff ID.';
                } elseif (isset($seenStaffIds[$staff])) {
                    $message = 'Duplicate staff ID in this file.';
                } elseif ($amount === false || $amount <= 0) {
                    $message = 'Contribution amount must be a positive number.';
                } else {
                    $seenStaffIds[$staff] = true;
                    $lookup = db()->prepare("SELECT id FROM members WHERE staff_id = ? AND status = 'active'");
                    $lookup->execute([$staff]);
                    $foundMember = $lookup->fetchColumn();
                    if (!$foundMember) {
                        $message = 'No active member matches this staff ID.';
                    } else {
                        $memberId = (int)$foundMember;
                        $duplicate = db()->prepare(
                            "SELECT id FROM financial_ledger WHERE member_id = ? AND transaction_type = 'contribution'
                             AND contribution_month = ? AND status = 'posted' LIMIT 1"
                        );
                        $duplicate->execute([$memberId, $monthDate]);
                        if ($duplicate->fetchColumn()) {
                            $message = 'A contribution for this member and month has already been imported.';
                        }
                    }
                }
                $rows[] = [
                    'line' => $line, 'staff' => $staff, 'member_id' => $memberId,
                    'amount' => $amount === false ? null : $amount,
                    'status' => $message === '' ? 'valid' : 'error', 'message' => $message,
                ];
            }
            fclose($handle);
            if (!$rows) {
                flash('error', 'The CSV does not contain any contribution records.');
                redirect('contributions');
            }
            $validCount = count(array_filter($rows, static fn(array $row): bool => $row['status'] === 'valid'));
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO csv_imports (file_name, contribution_month, status, total_rows, valid_rows, error_rows, imported_by)
                 VALUES (?, ?, 'preview', ?, ?, ?, ?)"
            );
            $stmt->execute([
                basename((string)$file['name']), $monthDate, count($rows), $validCount, count($rows) - $validCount, $admin['id'],
            ]);
            $importId = (int)$pdo->lastInsertId();
            $insert = $pdo->prepare(
                'INSERT INTO csv_import_rows (import_id, row_number, staff_id, member_id, amount, status, message)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($rows as $row) {
                $insert->execute([
                    $importId, $row['line'], $row['staff'], $row['member_id'], $row['amount'], $row['status'], $row['message'],
                ]);
            }
            audit((int)$admin['id'], 'contribution_csv_previewed', 'csv_import', $importId, ['rows' => count($rows)]);
            $pdo->commit();
            redirect('import_preview', ['id' => $importId]);

        case 'remove_import_error':
            $admin = require_admin();
            $importId = (int)($_POST['import_id'] ?? 0);
            $rowId = (int)($_POST['row_id'] ?? 0);
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status FROM csv_imports WHERE id = ? FOR UPDATE');
            $stmt->execute([$importId]);
            $batchStatus = $stmt->fetchColumn();
            if ($batchStatus !== 'preview') {
                $pdo->rollBack();
                flash('error', 'This import is no longer awaiting review.');
                redirect('contributions');
            }
            $stmt = $pdo->prepare(
                "SELECT id FROM csv_import_rows WHERE id = ? AND import_id = ? AND status = 'error' FOR UPDATE"
            );
            $stmt->execute([$rowId, $importId]);
            if (!$stmt->fetchColumn()) {
                $pdo->rollBack();
                flash('error', 'This errored row was not found in the pending import.');
                redirect('import_preview', ['id' => $importId]);
            }
            $pdo->prepare(
                'DELETE FROM csv_import_rows WHERE id = ? AND import_id = ? AND status = \'error\''
            )->execute([$rowId, $importId]);
            $pdo->prepare(
                'UPDATE csv_imports SET total_rows = total_rows - 1, error_rows = error_rows - 1 WHERE id = ?'
            )->execute([$importId]);
            audit((int)$admin['id'], 'contribution_csv_error_row_removed', 'csv_import', $importId, ['row_id' => $rowId]);
            $pdo->commit();
            flash('success', 'The errored row has been removed from the pending import.');
            redirect('import_preview', ['id' => $importId]);

        case 'confirm_import':
            $admin = require_admin();
            $importId = (int)($_POST['import_id'] ?? 0);
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM csv_imports WHERE id = ? FOR UPDATE');
            $stmt->execute([$importId]);
            $batch = $stmt->fetch();
            if (!$batch || $batch['status'] !== 'preview') {
                $pdo->rollBack();
                flash('error', 'This import is no longer awaiting confirmation.');
                redirect('contributions');
            }
            $stmt = $pdo->prepare(
                "SELECT r.* FROM csv_import_rows r WHERE r.import_id = ? AND r.status = 'valid' ORDER BY r.row_number"
            );
            $stmt->execute([$importId]);
            $validRows = $stmt->fetchAll();
            if (!$validRows) {
                $pdo->rollBack();
                flash('error', 'There are no valid contributions to import. Correct the CSV and upload it again.');
                redirect('import_preview', ['id' => $importId]);
            }
            $existing = $pdo->prepare(
                "SELECT id FROM financial_ledger WHERE member_id = ? AND transaction_type = 'contribution'
                 AND contribution_month = ? AND status = 'posted' LIMIT 1"
            );
            $insert = $pdo->prepare(
                "INSERT INTO financial_ledger
                 (transaction_ref, member_id, transaction_type, amount, contribution_month,
                  authorization_details, payment_reference, authorized_by, import_id)
                 VALUES (?, ?, 'contribution', ?, ?, ?, ?, ?, ?)"
            );
            $markRow = $pdo->prepare("UPDATE csv_import_rows SET status = 'imported' WHERE id = ?");
            $markDuplicate = $pdo->prepare(
                "UPDATE csv_import_rows SET status = 'error', message = 'A contribution for this member and month was posted after preview.' WHERE id = ?"
            );
            $importedCount = 0;
            $lateDuplicateCount = 0;
            foreach ($validRows as $row) {
                $existing->execute([$row['member_id'], $batch['contribution_month']]);
                if ($existing->fetchColumn()) {
                    $markDuplicate->execute([$row['id']]);
                    $lateDuplicateCount++;
                    continue;
                }
                $insert->execute([
                    bin2hex(random_bytes(12)), $row['member_id'], $row['amount'], $batch['contribution_month'],
                    'Monthly salary deduction CSV import #' . $importId, null, $admin['id'], $importId,
                ]);
                $markRow->execute([$row['id']]);
                $importedCount++;
            }
            if ($lateDuplicateCount > 0) {
                $pdo->prepare(
                    'UPDATE csv_imports SET valid_rows = valid_rows - ?, error_rows = error_rows + ? WHERE id = ?'
                )->execute([$lateDuplicateCount, $lateDuplicateCount, $importId]);
            }
            $pdo->prepare("UPDATE csv_imports SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$importId]);
            audit((int)$admin['id'], 'contribution_csv_imported', 'csv_import', $importId, [
                'imported_rows' => $importedCount, 'late_duplicates' => $lateDuplicateCount,
            ]);
            $pdo->commit();
            flash('success', $importedCount . ' contributions have been added to the financial ledger.'
                . ($lateDuplicateCount > 0 ? ' ' . $lateDuplicateCount . ' rows became duplicates after preview and were excluded.' : ''));
            redirect('import_summary', ['id' => $importId]);
    }
}

if ($page === 'loan_deductions_template') {
    require_admin();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="asup-nilest-loan-deductions-template.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['staff_id', 'loan_reference', 'amount', 'payment_reference']);
    fputcsv($output, ['ASUP-0001', 'LOAN-000001', '5000.00', 'PAYROLL-OCT-001']);
    fclose($output);
    exit;
}

if ($page === 'template') {
    require_admin();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="asup-nilest-contributions-template.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['staff_id', 'amount']);
    fputcsv($output, ['ASUP-0001', '5000.00']);
    fclose($output);
    exit;
}

if ($page === 'error_report') {
    $admin = require_admin();
    $importId = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare(
        "SELECT i.file_name, i.contribution_month, r.row_number, r.staff_id, r.amount, r.message
         FROM csv_import_rows r JOIN csv_imports i ON i.id = r.import_id
         WHERE r.import_id = ? AND r.status = 'error' ORDER BY r.row_number"
    );
    $stmt->execute([$importId]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contribution-import-' . $importId . '-errors.csv"');
    $output = fopen('php://output', 'wb');
    fputcsv($output, ['source_file', 'contribution_month', 'row_number', 'staff_id', 'amount', 'error']);
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($output, [$row['file_name'], $row['contribution_month'], $row['row_number'], $row['staff_id'], $row['amount'], $row['message']]);
    }
    fclose($output);
    exit;
}

$user = current_user();
if (in_array($page, ['dashboard', 'profile', 'requests', 'loans', 'statement', 'notifications', 'members', 'contributions', 'reports', 'announcements', 'audit', 'import_preview', 'import_summary'], true) && !$user) {
    require_login();
}
if ($user && $page === 'notifications' && $user['role'] !== 'member') {
    http_response_code(403);
    exit('Member access is required.');
}
if ($user && $user['role'] === 'admin'
    && in_array($page, ['members', 'contributions', 'reports', 'announcements', 'audit', 'import_preview', 'import_summary'], true)) {
    require_admin();
}
if ($user && $user['role'] === 'member' && in_array($page, ['members', 'contributions', 'reports', 'announcements', 'audit', 'import_preview', 'import_summary'], true)) {
    http_response_code(403);
    exit('Administrator access is required.');
}
if ($user && $user['role'] === 'member' && in_array($user['member_status'], ['pending', 'rejected'], true)
    && in_array($page, ['profile', 'requests', 'loans', 'statement'], true)) {
    redirect('dashboard');
}

$allowedPages = ['home', 'about', 'membership', 'contact', 'register', 'login', 'dashboard', 'profile', 'requests', 'loans', 'statement', 'notifications', 'members', 'contributions', 'reports', 'announcements', 'audit', 'import_preview', 'import_summary'];
if (!in_array($page, $allowedPages, true)) {
    http_response_code(404);
    $page = 'home';
}

$flashes = take_flashes();
$title = ucfirst(str_replace('_', ' ', $page));
require __DIR__ . '/views/layout.php';
