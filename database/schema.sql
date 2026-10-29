CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    staff_id VARCHAR(80) NOT NULL UNIQUE,
    full_name VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    department VARCHAR(150) NOT NULL DEFAULT '',
    monthly_contribution DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'active', 'rejected', 'terminated') NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS csv_imports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    contribution_month DATE NOT NULL,
    status ENUM('preview', 'completed') NOT NULL DEFAULT 'preview',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    error_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    CONSTRAINT fk_import_admin FOREIGN KEY (imported_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(64) NOT NULL UNIQUE,
    member_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('contribution', 'withdrawal', 'adjustment', 'loan_disbursement', 'loan_repayment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    contribution_month DATE NULL,
    transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    authorization_details VARCHAR(500) NOT NULL,
    payment_reference VARCHAR(190) NULL,
    authorized_by BIGINT UNSIGNED NOT NULL,
    import_id BIGINT UNSIGNED NULL,
    status ENUM('posted', 'reversed') NOT NULL DEFAULT 'posted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_member_contribution_month (member_id, transaction_type, contribution_month),
    KEY idx_ledger_member_date (member_id, transaction_date),
    CONSTRAINT fk_ledger_member FOREIGN KEY (member_id) REFERENCES members(id),
    CONSTRAINT fk_ledger_authorizer FOREIGN KEY (authorized_by) REFERENCES users(id),
    CONSTRAINT fk_ledger_import FOREIGN KEY (import_id) REFERENCES csv_imports(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS csv_import_rows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    staff_id VARCHAR(80) NOT NULL,
    member_id BIGINT UNSIGNED NULL,
    amount DECIMAL(12,2) NULL,
    status ENUM('valid', 'error', 'imported') NOT NULL,
    message VARCHAR(500) NOT NULL DEFAULT '',
    CONSTRAINT fk_import_row_batch FOREIGN KEY (import_id) REFERENCES csv_imports(id) ON DELETE CASCADE,
    CONSTRAINT fk_import_row_member FOREIGN KEY (member_id) REFERENCES members(id),
    KEY idx_import_rows (import_id, row_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    request_type ENUM('contribution_increase', 'contribution_reduction', 'withdrawal', 'termination') NOT NULL,
    amount DECIMAL(12,2) NULL,
    proposed_contribution DECIMAL(12,2) NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewer_id BIGINT UNSIGNED NULL,
    reviewer_notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    CONSTRAINT fk_request_member FOREIGN KEY (member_id) REFERENCES members(id),
    CONSTRAINT fk_request_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id),
    KEY idx_requests_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id BIGINT UNSIGNED NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    principal_amount DECIMAL(12,2) NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'active', 'rejected', 'settled') NOT NULL DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    CONSTRAINT fk_loan_member FOREIGN KEY (member_id) REFERENCES members(id),
    CONSTRAINT fk_loan_approver FOREIGN KEY (approved_by) REFERENCES users(id),
    KEY idx_loans_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loan_repayments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_reference VARCHAR(190) NOT NULL,
    received_by BIGINT UNSIGNED NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_repayment_loan FOREIGN KEY (loan_id) REFERENCES loans(id),
    CONSTRAINT fk_repayment_receiver FOREIGN KEY (received_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    published TINYINT(1) NOT NULL DEFAULT 1,
    author_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcement_author FOREIGN KEY (author_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    body VARCHAR(1000) NOT NULL,
    link VARCHAR(255) NOT NULL DEFAULT 'index.php?page=notifications',
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id),
    KEY idx_notifications_user_read (user_id, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    subject VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
