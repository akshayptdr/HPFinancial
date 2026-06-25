# HP Financial — CA Practice Management Software

Core PHP (no framework) + MySQL. Phase 1.

## Stack
- PHP 8.1+ (tested on XAMPP PHP 8.2)
- MySQL / MariaDB
- Bootstrap-free custom UI (theme in `public/assets/css/theme.css`)

## Local setup (XAMPP)

1. **Database** — already created as `hp`. Import schema + seed:
   ```
   "C:\xampp\mysql\bin\mysql.exe" -u root hp < database/schema.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root hp < database/seed.sql
   ```
2. **Config** — copy `.env.example` to `.env` and set DB creds + a 32-byte `APP_KEY`.
   Current `.env` is configured for `127.0.0.1 / root / root / hp`.
   Generate a key: `php -r "echo 'base64:'.base64_encode(random_bytes(32));"`
3. **Run** (built-in server):
   ```
   "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t public
   ```
   Open http://127.0.0.1:8000
   *(Or via Apache: point a vhost / the URL to the `public/` folder.)*

## First login
- **Mobile:** `9999999999`
- **Password:** `admin123`
- You'll be forced to set a new password on first login.

## Daily reminders (cron)
```
php cron/reminders.php
```
Schedule once a day (Hostinger hPanel → Cron Jobs, or Windows Task Scheduler).

## Modules (Phase 1)
- **Auth & RBAC** — mobile + password login, admin/employee roles, permission matrix.
- **Employees** — add (auto-generated password), edit, activate/deactivate, reset password.
- **Leads** — capture, pipeline, follow-up timeline, masters (types/categories), convert → customer.
- **Customers** — profile with shared capture-once fields (Firm/GST/PAN/Aadhaar), documents, service assignment.
- **Services (10)** — config-driven job engine: Income Tax, GST, Accounting, Loan & Subsidy,
  Mutual Fund, Insurance, Government Department (encrypted portal credentials),
  Certificate, Deeds & Agreement, Company Compliances. Shared **fees / part-payment** engine.
- **Work Board** — firm-wide jobs with overdue/due-soon highlighting + filters.
- **Dashboard / Reports / Notifications** — role-aware.

## Project layout
```
public/      web root (front controller + assets)
app/Core     framework primitives (Router, Database, Auth, ...)
app/Controllers, app/Models, app/Views
app/Support/ServiceConfig.php   ← per-service field definitions
config/      config + routes
database/    schema.sql, seed.sql
cron/        reminders.php
design/      static HTML mockups (reference)
docs: PRD.md / .pdf, TECHNICAL_DESIGN.md / .pdf
```

## Deploying to Hostinger
1. Set PHP 8.1+ in hPanel.
2. Create MySQL DB/user; import `database/schema.sql` + `seed.sql`.
3. Put the app outside `public_html` and the contents of `public/` inside
   `public_html` (or point the domain document root to `public/`).
4. Set `.env` (DB creds + `APP_KEY`); make `storage/` writable.
5. Add the daily cron for `cron/reminders.php`. Enable free SSL.
