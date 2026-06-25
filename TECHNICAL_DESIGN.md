# HP Financial — Technical Design Document (TDD)

| | |
|---|---|
| **Document** | Technical Design Document |
| **Product** | HP Financial — CA Practice Management Software |
| **Version** | 1.0 (Phase 1) |
| **Date** | 2026-06-25 |
| **Companion** | `PRD.md` (requirements). This TDD is the engineering spec. |
| **Status** | Draft for review |

> This document turns the PRD into a build-ready engineering specification:
> architecture, database DDL, routing, class design, shared subsystems,
> validation, security, deployment, and the screen inventory that drives the
> design phase. **No development begins until this TDD and the screen designs
> are approved.**

---

## 1. Architecture & Request Lifecycle

**Pattern:** hand-rolled **MVC** on a **single front controller**. No framework.

**Stack:** PHP 8.1+, MySQL/MariaDB (PDO), Bootstrap 5, vanilla JS + light jQuery.

**Request lifecycle:**
1. All web traffic hits `public/index.php` (via `.htaccess` rewrite).
2. `index.php` bootstraps: load `.env` → `config/config.php` → autoloader →
   start `Session` → build `Request`.
3. `Router` matches `METHOD + path` against `config/routes.php` to a
   `Controller@method` plus a middleware list.
4. **Middleware** runs in order (`AuthMiddleware`, then `RoleMiddleware` with the
   route's required permission). On failure → redirect to login / 403.
5. The **Controller** validates input (`Validator`, `Csrf`), calls **Models**
   (PDO prepared statements), and renders a **View** within a layout.
6. View output is escaped via `e()`; flash messages and the auth user are
   available to all views.

```
Browser ─▶ public/index.php ─▶ Router ─▶ [Middleware] ─▶ Controller
                                                            │
                                              Model (PDO) ◀─┤
                                                            ▼
                                                    View + Layout ─▶ HTML
```

---

## 2. Tech Stack & Dependencies

| Concern | Choice |
|---|---|
| Language | PHP 8.1+ |
| DB | MySQL 8 / MariaDB 10.4+, via PDO (prepared statements only) |
| Front-end | Bootstrap 5 (local), vanilla JS, minimal jQuery |
| Config | `vlucas/phpdotenv` (read `.env`) |
| Encryption | `sodium_*` (libsodium, bundled in PHP 8.1) for portal credentials |
| Autoload | Composer PSR-4 (`App\` → `app/`) |

**Removed from Phase 1:** PHPMailer / any mailer — Phase 1 is **in-app
notifications only** (no email/SMS/WhatsApp).

`composer.json` (Phase 1): `vlucas/phpdotenv` only.

---

## 3. Directory Structure

```
hp-financial/
├── public/                # web root (only this is exposed)
│   ├── index.php          # front controller
│   ├── .htaccess          # rewrite to index.php; deny dotfiles
│   └── assets/            # css, js, img, vendor (bootstrap)
├── app/
│   ├── Core/              # Router, Database, Controller, Model, Auth,
│   │                      # Session, Csrf, Validator, Request, Crypto, View
│   ├── Middleware/        # AuthMiddleware, RoleMiddleware
│   ├── Controllers/       # + Controllers/Services/* (one per service)
│   ├── Models/            # User, Lead, Customer, ServicePayment, *Job ...
│   ├── Views/             # layouts/, auth/, leads/, customers/, services/ ...
│   └── Helpers/functions.php
├── config/                # config.php, routes.php
├── database/              # schema.sql, seed.sql, migrations/
├── storage/               # logs/, uploads/customers/{id}/  (deny web access)
├── cron/                  # reminders.php (daily)
├── tools/                 # md2pdf.py
├── .env / .env.example / .gitignore / composer.json
```

---

## 4. Configuration & Environment

`.env` (never committed):
```
APP_ENV=production
APP_URL=https://crm.example.com
APP_KEY=base64:...          # 32-byte key for sodium credential encryption
DB_HOST=localhost
DB_NAME=hp_financial
DB_USER=hp_user
DB_PASS=********
SESSION_NAME=hpf_sess
REMINDER_LEAD_DAYS=3        # default; also overridable in settings table
UPLOAD_MAX_MB=5
```
`config/config.php` exposes typed constants (e.g. `APP_KEY`, `DB_*`, paths) and
fails fast if `APP_KEY` is missing.

---

## 5. Routing & Route Map

`config/routes.php` returns an array of routes:
`[method, path, handler, middleware[], permission]`. Path params use `{id}`.

| Method | Path | Handler | Access (permission) |
|---|---|---|---|
| GET/POST | `/login` | `AuthController@login` | guest |
| POST | `/logout` | `AuthController@logout` | auth |
| GET/POST | `/password/change` | `AuthController@changePassword` | auth |
| GET | `/` | `DashboardController@index` | auth |
| GET | `/employees` | `EmployeeController@index` | `employees.view` |
| GET/POST | `/employees/create` | `EmployeeController@create` | `employees.manage` |
| GET/POST | `/employees/{id}/edit` | `EmployeeController@edit` | `employees.manage` |
| POST | `/employees/{id}/status` | `EmployeeController@toggleStatus` | `employees.manage` |
| POST | `/employees/{id}/reset-password` | `EmployeeController@resetPassword` | `employees.manage` |
| GET/POST | `/roles` | `RoleController@index` | `roles.manage` |
| GET | `/leads` | `LeadController@index` | `leads.view` |
| GET/POST | `/leads/create` | `LeadController@create` | `leads.create` |
| GET/POST | `/leads/{id}` | `LeadController@show` | `leads.view` |
| POST | `/leads/{id}/activity` | `LeadController@addActivity` | `leads.edit` |
| POST | `/leads/{id}/assign` | `LeadController@assign` | `leads.edit` |
| POST | `/leads/{id}/convert` | `LeadController@convert` | `customers.create` |
| GET | `/customers` | `CustomerController@index` | `customers.view` |
| GET/POST | `/customers/create` | `CustomerController@create` | `customers.create` |
| GET | `/customers/{id}` | `CustomerController@show` | `customers.view` |
| POST | `/customers/{id}/update` | `CustomerController@update` | `customers.edit` |
| POST | `/customers/{id}/documents` | `CustomerController@uploadDoc` | `customers.edit` |
| POST | `/customers/{id}/services` | `CustomerController@assignServices` | `customers.edit` |
| GET/POST | `/customers/{id}/{service}/jobs` | `Services\{Service}Controller@store` | `services.edit` |
| GET/POST | `/jobs/{service}/{id}/edit` | `Services\{Service}Controller@edit` | `services.edit` |
| POST | `/jobs/{service}/{id}/payments` | `PaymentController@store` | `payments.record` |
| GET | `/work-board` | `ServiceController@board` | `services.view` |
| GET | `/reports` | `ReportController@index` | `reports.view` |
| GET | `/reports/{type}` | `ReportController@show` | `reports.view` |
| GET | `/reports/{type}/export` | `ReportController@export` | `reports.view` |
| GET | `/notifications` | `NotificationController@index` | auth |
| POST | `/notifications/{id}/read` | `NotificationController@markRead` | auth |
| GET/POST | `/settings` | `SettingController@index` | `settings.manage` |
| GET/POST | `/masters/{type}` | `ServiceController@masters` | `masters.manage` |

`{service}` ∈ `income-tax, gst, accounting, loan-subsidy, mutual-fund,
insurance, govt-dept, certificate, deeds-agreement, company-compliance`.

---

## 6. Core Class Design (`app/Core`)

| Class | Responsibility | Key methods |
|---|---|---|
| `Router` | match request → handler; run middleware | `add()`, `dispatch(Request)` |
| `Database` | single PDO connection (singleton) | `pdo()`, `instance()` |
| `Model` | base: CRUD via PDO prepared statements | `find()`, `all()`, `where()`, `insert()`, `update()`, `delete()`, `paginate()` |
| `Controller` | base: render views, redirects, JSON, auth helpers | `view()`, `redirect()`, `json()`, `user()`, `authorize($perm)` |
| `Auth` | login/logout, current user, hashing | `attempt(mobile,pwd)`, `login(user)`, `logout()`, `user()`, `check()` |
| `Session` | safe session wrapper | `start()`, `get/set/forget`, `flash()`, `regenerate()` |
| `Csrf` | token issue + verify | `token()`, `field()`, `verify($t)` |
| `Validator` | rule-based input validation | `make($data,$rules)`, `fails()`, `errors()` |
| `Request` | encapsulate input/files/method | `input()`, `file()`, `method()`, `only()` |
| `Crypto` | encrypt/decrypt portal credentials | `encrypt($plain)`, `decrypt($cipher)` (sodium secretbox + `APP_KEY`) |
| `View` | render PHP templates into layout | `render($tpl,$data,$layout)` |

**Middleware**
- `AuthMiddleware` — redirect to `/login` if not authenticated; enforce
  `must_change_password` → `/password/change`.
- `RoleMiddleware` — check the route permission against the user's role
  permissions; 403 page otherwise.

**Models** — one per table; service job models extend `Model` and add
service-specific helpers (e.g. default due-date computation). `ServicePayment`
model centralizes the fees engine (§9.2).

---

## 7. Database Schema (DDL)

Engine `InnoDB`, charset `utf8mb4`. All tables have `id BIGINT UNSIGNED AUTO_
INCREMENT PRIMARY KEY` and (where noted) timestamps. FKs use `ON DELETE
RESTRICT` except logs/children which `CASCADE`. **Shared capture-once fields
(`firm_name`, `gst_number`, `pan_number`, `aadhaar_number`) live on `customers`
only** — per-service job tables reference the customer and do not duplicate them.

### 7.1 Auth, roles, permissions

```sql
CREATE TABLE roles (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(50) NOT NULL,
  slug      VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(80) NOT NULL,
  slug      VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
  role_id        BIGINT UNSIGNED NOT NULL,
  permission_id  BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name                 VARCHAR(120) NOT NULL,
  mobile               VARCHAR(15)  NOT NULL UNIQUE,   -- login identifier
  email                VARCHAR(150) NULL,
  password_hash        VARCHAR(255) NOT NULL,
  role_id              BIGINT UNSIGNED NOT NULL,
  status               ENUM('active','inactive') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at        DATETIME NULL,
  created_by           BIGINT UNSIGNED NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.2 Lead masters & leads

```sql
CREATE TABLE lead_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lead_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leads (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  mobile        VARCHAR(15)  NOT NULL,
  lead_type_id  BIGINT UNSIGNED NULL,
  category_id   BIGINT UNSIGNED NULL,           -- interested category
  state         VARCHAR(80) NULL,
  district      VARCHAR(80) NULL,
  tehsil        VARCHAR(80) NULL,
  village       VARCHAR(80) NULL,
  contact_person VARCHAR(120) NULL,
  follow_up_date DATE NULL,                       -- optional
  status        ENUM('new','contacted','qualified','won','lost')
                NOT NULL DEFAULT 'new',
  assigned_to   BIGINT UNSIGNED NULL,
  notes         TEXT NULL,
  created_by    BIGINT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_type_id) REFERENCES lead_types(id),
  FOREIGN KEY (category_id)  REFERENCES lead_categories(id),
  FOREIGN KEY (assigned_to)  REFERENCES users(id),
  INDEX (status), INDEX (assigned_to), INDEX (follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lead_activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  type ENUM('note','call','meeting','status_change') NOT NULL DEFAULT 'note',
  description TEXT NULL,
  follow_up_at DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  INDEX (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.3 Customers (shared capture-once fields live here)

```sql
CREATE TABLE customers (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id        BIGINT UNSIGNED NULL,           -- source lead
  name           VARCHAR(120) NOT NULL,
  mobile         VARCHAR(15)  NOT NULL,
  firm_name      VARCHAR(160) NULL,              -- SHARED
  pan_number     VARCHAR(10)  NULL,              -- SHARED
  aadhaar_number VARCHAR(12)  NULL,              -- SHARED
  gst_number     VARCHAR(15)  NULL,              -- SHARED
  email          VARCHAR(150) NULL,
  bank_details   VARCHAR(255) NULL,
  state          VARCHAR(80) NULL,
  district       VARCHAR(80) NULL,
  tehsil         VARCHAR(80) NULL,
  village        VARCHAR(80) NULL,
  contact_person VARCHAR(120) NULL,
  customer_type  VARCHAR(40) NULL,
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  assigned_to    BIGINT UNSIGNED NULL,
  created_by     BIGINT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX (firm_name), INDEX (gst_number), INDEX (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_attributes (       -- extensible shared fields
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  `key` VARCHAR(60) NOT NULL,
  `value` VARCHAR(255) NULL,
  UNIQUE KEY uq_cust_key (customer_id, `key`),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  doc_type ENUM('pan','aadhaar','bank_passbook','other') NOT NULL,
  text_value VARCHAR(100) NULL,
  file_path VARCHAR(255) NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.4 Services master, file statuses, customer_services

```sql
CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,       -- income_tax, gst, ...
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE file_statuses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  service_id  BIGINT UNSIGNED NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cust_service (customer_id, service_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id)  REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.5 Shared fees engine — `service_payments` (polymorphic)

```sql
CREATE TABLE service_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_type VARCHAR(30) NOT NULL,    -- income_tax, gst, accounting, ...
  job_id   BIGINT UNSIGNED NOT NULL,
  amount   DECIMAL(12,2) NOT NULL,
  payment_mode ENUM('cash','bank') NOT NULL,
  received_date DATE NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  remarks VARCHAR(255) NULL,
  FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX (job_type, job_id), INDEX (received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.6 Per-service job tables

Common columns on every `*_jobs` table: `id`, `customer_id` (FK→customers),
`file_status_id` (FK→file_statuses), `fees_amount DECIMAL(12,2)`, `comment TEXT`,
`assigned_to` (FK→users), `created_by`, `created_at`, `updated_at`. Receipts come
from `service_payments`. **No `firm_name`/`gst_number` columns** (read from
customer).

```sql
CREATE TABLE income_tax_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sub_type ENUM('itr','tds','audit','other') NOT NULL,
  title VARCHAR(120) NULL,            -- 'other' free-text
  form_type ENUM('24Q','26Q') NULL,  -- TDS only
  financial_year VARCHAR(9) NULL,     -- e.g. 2025-26
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id), INDEX (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gst_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  gst_user_id VARCHAR(80) NULL,
  form_name VARCHAR(80) NULL,
  filing_type ENUM('monthly','quarterly') NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;  -- gst_number is on customers (shared)

CREATE TABLE gst_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  gst_profile_id BIGINT UNSIGNED NULL,
  category ENUM('return','registration','audit') NOT NULL,
  return_type ENUM('gstr1','gstr3b') NULL,       -- returns
  audit_form_type ENUM('9','9_9c') NULL,         -- audit
  trn_number VARCHAR(20) NULL,                    -- registration
  arn_number VARCHAR(20) NULL,                    -- registration
  gst_user_id VARCHAR(80) NULL,
  financial_year VARCHAR(9) NULL,
  period_label VARCHAR(20) NULL,                  -- 'Jun-2026' / 'Apr-Jun 2026'
  due_date DATE NULL,                             -- default 10th/20th
  filing_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (gst_profile_id) REFERENCES gst_profiles(id),
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id), INDEX (category), INDEX (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE accounting_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  frequency ENUM('monthly','quarterly','yearly') NOT NULL,
  financial_year VARCHAR(9) NULL,
  period_label VARCHAR(20) NULL,
  due_date DATE NULL,                             -- default 5th
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id), INDEX (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE loan_subsidy_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sub_type ENUM('udyam','cma_report','loan') NOT NULL,
  bank_name VARCHAR(120) NULL,                    -- CMA & Loan
  loan_amount DECIMAL(14,2) NULL,
  loan_officer_name VARCHAR(120) NULL,
  loan_officer_number VARCHAR(15) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mutual_fund_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  account_open TINYINT(1) NOT NULL DEFAULT 0,
  ucc_number VARCHAR(40) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mutual_fund_investments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  investment_type ENUM('sip','lumpsum','transfer') NOT NULL,
  target_amount DECIMAL(14,2) NULL,
  achieved_amount DECIMAL(14,2) NULL,
  FOREIGN KEY (job_id) REFERENCES mutual_fund_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE insurance_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  referred_by VARCHAR(120) NULL,
  company_name VARCHAR(120) NULL,
  policy_type ENUM('fresh','port') NULL,
  status VARCHAR(60) NULL,
  claim_amount DECIMAL(14,2) NULL,       -- claim assist
  follow_up_date DATE NULL,              -- claim assist
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE insurance_job_types (       -- multi-select
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  insurance_job_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL,             -- health/term/vehicle/claim_assist/...
  FOREIGN KEY (insurance_job_id) REFERENCES insurance_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE govt_dept_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sub_type ENUM('audit','gst','tds') NOT NULL,
  officer_name VARCHAR(120) NULL,
  officer_contact VARCHAR(15) NULL,
  contact_person VARCHAR(120) NULL,
  audit_type VARCHAR(80) NULL,            -- audit
  gst_form_type VARCHAR(20) NULL,         -- gst (GSTR-7/other)
  gst_user_id VARCHAR(80) NULL,
  tds_form_type ENUM('24G','26Q','24R') NULL,  -- tds
  tan_number VARCHAR(15) NULL,
  financial_year VARCHAR(9) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id), INDEX (sub_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE govt_dept_credentials (     -- ENCRYPTED values (§9.4)
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  cred_type ENUM('gst_portal','traces','it_portal','ain_24q','ain_26q') NOT NULL,
  username VARBINARY(512) NULL,          -- ciphertext
  password VARBINARY(512) NULL,          -- ciphertext
  FOREIGN KEY (job_id) REFERENCES govt_dept_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE certificate_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  form_name VARCHAR(120) NULL,
  certificate_type VARCHAR(120) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE deeds_agreement_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(120) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE company_compliance_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sub_type ENUM('audit','company_registration','compliances','other') NOT NULL,
  form_name VARCHAR(120) NULL,
  cs_number VARCHAR(40) NULL,            -- company registration
  cs_name VARCHAR(120) NULL,
  cs_contact VARCHAR(15) NULL,
  financial_year VARCHAR(9) NULL,
  due_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  assigned_to BIGINT UNSIGNED NULL, created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  INDEX (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.7 System tables

```sql
CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  message VARCHAR(255) NULL,
  link VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_type ENUM('lead_followup','service_job') NOT NULL,
  source_ref VARCHAR(40) NULL,           -- service code for service_job
  source_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  remind_on DATE NOT NULL,
  channel ENUM('in_app') NOT NULL DEFAULT 'in_app',  -- Phase 1: in-app only
  status ENUM('pending','sent') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  INDEX (remind_on, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(60) NOT NULL UNIQUE,
  `value` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,           -- created/updated/deleted/payment...
  entity VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  detail VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (entity, entity_id), INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.8 Seed data (`seed.sql`)
- Roles: `admin`, `employee`.
- Permissions: `employees.view/manage`, `roles.manage`, `leads.view/create/edit`,
  `customers.view/create/edit`, `services.view/edit`, `payments.record`,
  `reports.view`, `settings.manage`, `masters.manage`. Admin → all; employee →
  operational subset (view/create/edit leads & customers, services.edit,
  payments.record, reports.view).
- One admin user (mobile + temp password, `must_change_password=1`).
- Services (10), file_statuses (Pending/In-Progress/Filed/Completed),
  lead_types (Referral, Social Media), lead_categories (sample).
- Settings: `reminder_lead_days=3`.

---

## 8. RBAC & Permission Matrix

| Permission | admin | employee |
|---|:--:|:--:|
| employees.view / manage | ✅ | ❌ |
| roles.manage / settings.manage / masters.manage | ✅ | ❌ |
| leads.view / create / edit | ✅ | ✅ |
| customers.view / create / edit | ✅ | ✅ |
| services.view / edit | ✅ | ✅ |
| payments.record | ✅ | ✅ (own jobs) |
| reports.view | ✅ | ✅ (scoped to self) |

Row-level rule: employees see/edit records **assigned to them**; admins see all.
Enforced in model query scopes + controller `authorize()`.

---

## 9. Shared Subsystems

### 9.1 Shared customer fields (capture-once)
`firm_name`, `gst_number`, `pan_number`, `aadhaar_number` are read from
`customers` and rendered **read-only** on every service-job form. Editable only
on the customer profile. Additional shared fields can be added via
`customer_attributes` without schema changes. Service controllers call a
`Customer::sharedFields($id)` helper to populate the read-only block.

### 9.2 Fees / part-payment engine
- A job's `fees_amount` is the agreed fee. Receipts are rows in
  `service_payments` keyed by (`job_type`,`job_id`).
- `ServicePayment::summary($jobType,$jobId)` → `[received, balance, count]`
  where `received = SUM(amount)`, `balance = fees_amount - received`.
- **Write rule:** allowed if `user.role=admin` OR `job.assigned_to=user.id`.
  Enforced in `PaymentController@store`.
- Every insert/update writes `activity_log` (`action='payment'`).

### 9.3 File uploads
- Accept `pdf/jpg/jpeg/png`, max `UPLOAD_MAX_MB`. Validate MIME + extension +
  size. Generate random filename; store at
  `storage/uploads/customers/{customer_id}/`. Persist relative `file_path`.
- `storage/` is outside web root / denied by `.htaccess`; downloads are streamed
  through an authenticated controller action (never a direct URL).

### 9.4 Credential encryption (Govt Dept portal logins)
- `Crypto::encrypt()` uses `sodium_crypto_secretbox` with a per-record nonce and
  the 32-byte `APP_KEY`; ciphertext stored as `VARBINARY`.
- Decrypted only when an authorized user (admin or job assignee) opens the job;
  shown masked with a reveal toggle. Never logged.

### 9.5 Notifications & reminders (in-app only)
- `cron/reminders.php` runs **daily** (Hostinger cron). For each open service job
  with a `due_date` and each lead `follow_up_date`, if
  `due_date - reminder_lead_days <= today` and no `reminder` exists → insert a
  `reminder` (`channel='in_app'`) and a `notification` for the assignee.
- Overdue jobs (due_date < today, not completed) surface on dashboard/work board.

### 9.6 Activity log
Written on create/update/delete of leads, customers, jobs, employees, and on all
payment changes. Viewable by admin.

---

## 10. Per-Service Module Design

- Each service has a controller in `app/Controllers/Services/` and a model per
  `*_jobs` table. A shared `partials/_fees.php` + `partials/_shared_fields.php`
  render the fees panel and read-only customer fields on every job form.
- **Conditional UI** (progressive disclosure, server-validated):
  - *Income Tax* `sub_type` toggles TDS `form_type` / `Other` title.
  - *GST* `category` toggles Returns vs Registration (TRN/ARN) vs Audit fields;
    `return_type` sets due-date default (10th/20th).
  - *Govt Dept* `sub_type=tds` reveals credential blocks (TAN→TRACES+IT;
    AIN-24Q; AIN-26Q) saved to `govt_dept_credentials` (encrypted).
  - *Insurance* multi-select types; `claim_assist` reveals claim fields.
  - *Mutual Fund* `account_open` reveals UCC; investment-type rows
    (target/achieved) added dynamically → `mutual_fund_investments`.
- Default due-date helpers compute 10th/20th (GST) and 5th (Accounting) from the
  selected period, editable by the user.

---

## 11. Validation Rules (server-side, `Validator`)

| Field | Rule |
|---|---|
| mobile | required, 10 digits `^[6-9]\d{9}$` |
| password (set) | required, min 8, confirmed |
| PAN | `^[A-Z]{5}[0-9]{4}[A-Z]$` (optional) |
| GST number | `^\d{2}[A-Z]{5}\d{4}[A-Z]\d[A-Z\d]{2}$` (optional) |
| Aadhaar | 12 digits (optional) |
| financial_year | `^\d{4}-\d{2}$` |
| amounts | numeric ≥ 0, 2 decimals |
| dates | valid date; `received_date` ≤ today |
| file upload | mime in allowlist, ≤ max MB |

All POST routes require a valid CSRF token; failures return 419 + flash.

---

## 12. UI Structure & Screen Inventory

**Layouts:** `auth.php` (centered card) and `app.php` (fixed sidebar + topbar +
content). **Shared components:** sidebar nav, topbar (search, notifications bell,
user menu), page header with actions, filter bar, data table + pagination, status
pills, cards/stat tiles, tabs, timeline, modals, toasts, empty states.

**Screens (designed in the next phase):** Login; Set/Reset password; Admin
dashboard; Employee dashboard; Employees list & add/edit; Roles & permissions;
Leads list, add/edit, detail (timeline); Lead masters; Customers list; Customer
profile (Profile/Documents/Services tabs); Convert-lead; Service job forms
(Income Tax, GST, Govt Dept, Insurance, Mutual Fund + shared pattern for the
rest); Fees/part-payment modal; Work board; Reports index + report view;
Notifications center; Settings & masters.

---

## 13. Security Design (checklist)
- Passwords: bcrypt; forced change on first login / after reset.
- Sessions: regenerate on login; httponly+secure cookies; idle timeout.
- CSRF on all writes; output escaping on all echoes; PDO prepared statements.
- RBAC in middleware + row-level scoping; admin-only masters/settings.
- Uploads validated + stored outside web root; authenticated download proxy.
- Portal credentials encrypted at rest (sodium + `APP_KEY`); masked in UI.
- `.htaccess` denies `.env`, `app/`, `storage/`, `config/`; force HTTPS.
- Errors logged to `storage/logs/`; generic error page in production.

---

## 14. Deployment (Hostinger)
- PHP 8.1+; create MySQL DB/user (hPanel) → `.env`.
- Point domain doc-root to `public/` (preferred) or use the
  app-outside-`public_html` fallback (adjust `index.php` paths).
- Import `database/schema.sql` then `seed.sql` (phpMyAdmin).
- Generate `APP_KEY`; set `storage/` writable (755), web-denied.
- Add **daily cron**: `php /home/.../cron/reminders.php`.
- Enable free SSL; force HTTPS. Configure DB backups.

---

## 15. Coding Conventions
- PSR-4 autoload (`App\`), PSR-12 style, 4-space indent.
- Controllers thin; business rules in models/services; no SQL in controllers.
- One class per file; `PascalCase` classes, `camelCase` methods, `snake_case`
  DB columns. All queries parameterized. Centralized helpers in
  `app/Helpers/functions.php` (`e()`, `url()`, `old()`, `flash()`, `can()`).
