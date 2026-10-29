# ASUP NILEST Savings & Credit Scheme

A PHP and MySQL application for XAMPP with public scheme information, member accounts, administrator workflows, salary-deduction CSV imports, savings statements, loan and request management, financial reports, and audit history.

## Requirements

- XAMPP with Apache, MySQL, and PHP 8.1 or later
- PHP extensions `pdo_mysql`, `fileinfo` (for standard PHP upload support)
- Internet access to load Ionicons from the unpkg CDN
- A new MySQL database named `asup_nilest_scheme`

## Install in XAMPP

1. Copy this project to `C:\xampp\htdocs\asup_nilest_loan`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. If your MySQL credentials are not the XAMPP defaults, edit `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `config.php`.
4. Visit `http://localhost/asup_nilest_loan/`. If the application database does not exist yet, the home page redirects to the setup page. The installer creates a new `asup_nilest_scheme` database when the configured MySQL account has permission, then prompts you to create the first administrator. The password must be at least 12 characters.
5. Sign in through `http://localhost/asup_nilest_loan/`.
6. If the configured MySQL account cannot create databases, create a new `asup_nilest_scheme` database in phpMyAdmin using the `utf8mb4_unicode_ci` collation, then reload the setup page.
7. Restrict or remove `setup.php` after installation. The installer will not create another administrator once a user exists.

The installer applies the idempotent table definitions in `database/schema.sql` whenever `setup.php` is opened. If an existing database is missing a table, visit `http://localhost/asup_nilest_loan/setup.php` to create missing tables; existing data is not dropped. To install manually, execute the schema SQL against the `asup_nilest_scheme` database before creating the first administrator through the installer.

## Main workflows

- **Membership:** Members register individually or an administrator imports a CSV roster. New applications stay pending until reviewed.
- **Contributions:** Administrators choose a month, upload a CSV, set the zero-based staff ID and amount column positions, review validation and duplicate results, then confirm valid rows. Error rows can be downloaded as a CSV report.
- **Savings:** Member statements and balances are calculated from posted ledger entries. Withdrawals are approved only when at least **₦1,000** remains.
- **Requests:** Members can request contribution increases or reductions, partial withdrawals, and membership termination. Termination is blocked while a loan remains outstanding.
- **Loans:** Members apply through the portal. Administrators approve/reject applications and record repayments with payment references.
- **Oversight:** Administrator reports show monthly posted contributions, expected contributions, members with outstanding deductions, and loan balances. Financial and administrative actions are logged; members receive in-app notices for membership decisions, request outcomes, and loan activity.

### Contribution CSV format

Download the template from **Administrator → Contributions**. By default, it has a header row with `staff_id,amount`. The import form accepts other column orders by their zero-based positions. Each staff ID must match an active member. Only one posted contribution per member per month is accepted.

### Bulk member roster format

The administrator member-management page accepts a CSV with a header row containing `staff_id,full_name,email,phone,department,monthly_contribution,password`. Passwords must be at least 10 characters. New records are pending membership approval. Distribute temporary credentials securely; active members can change their passwords from their profile.

## Security and deployment notes

- Use HTTPS and non-default database credentials before exposing this application beyond a local development machine.
- Use a strong, unique administrator password and keep database backups protected.
- The application uses prepared PDO statements, password hashing, session regeneration on login, CSRF tokens for forms, role checks, and output escaping.
- This starter application does not send email, process payments, or connect to a bank/payroll provider. Record external payment references during administrator confirmation.
- Configure PHP's `upload_max_filesize` and `post_max_size` if payroll CSV files exceed the defaults.
