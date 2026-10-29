# ASUP NILEST Savings & Credit Scheme

A PHP and MySQL application for XAMPP with public scheme information, member accounts, administrator workflows, salary-deduction CSV imports, savings statements, loan and request management, financial reports, and audit history.

## Main workflows

- **Membership:** Members register individually or an administrator imports a CSV roster. New applications stay pending until reviewed.
- **Contributions:** Administrators choose a month, upload a CSV, set the zero-based staff ID and amount column positions, review validation and duplicate results, then confirm valid rows. Error rows can be downloaded as a CSV report.
- **Savings:** Member statements and balances are calculated from posted ledger entries. Withdrawals are approved only when at least **₦1,000** remains.
- **Requests:** Members can request contribution increases or reductions, partial withdrawals, and membership termination. Termination is blocked while a loan remains outstanding.
- **Loans:** Members apply through the portal. Administrators approve/reject applications and record repayments with payment references.
- **Oversight:** Administrator reports show monthly posted contributions, expected contributions, members with outstanding deductions, and loan balances. Financial and administrative actions are logged; members receive in-app notices for membership decisions, request outcomes, and loan activity.
