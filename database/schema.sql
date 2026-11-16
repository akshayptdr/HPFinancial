-- HP Financial — schema (MySQL 8 / MariaDB). Target DB: hp
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- ===== Auth / RBAC =====
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE permissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
  role_id BIGINT UNSIGNED NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(15) NOT NULL UNIQUE,
  email VARCHAR(150) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Lead masters & leads =====
DROP TABLE IF EXISTS lead_activities;
DROP TABLE IF EXISTS leads;
DROP TABLE IF EXISTS lead_types;
DROP TABLE IF EXISTS lead_categories;

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
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(15) NOT NULL,
  lead_type_id BIGINT UNSIGNED NULL,
  category_id BIGINT UNSIGNED NULL,
  state VARCHAR(80) NULL,
  district VARCHAR(80) NULL,
  tehsil VARCHAR(80) NULL,
  village VARCHAR(80) NULL,
  contact_person VARCHAR(120) NULL,
  contact_person_mobile VARCHAR(20) NULL,
  follow_up_date DATE NULL,
  status ENUM('new','contacted','qualified','won','lost') NOT NULL DEFAULT 'new',
  assigned_to BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_type_id) REFERENCES lead_types(id),
  FOREIGN KEY (category_id) REFERENCES lead_categories(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX(status), INDEX(assigned_to), INDEX(follow_up_date)
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
  INDEX(lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Customers =====
DROP TABLE IF EXISTS customer_documents;
DROP TABLE IF EXISTS customer_services;
DROP TABLE IF EXISTS customer_attributes;
DROP TABLE IF EXISTS customers;

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  mobile VARCHAR(15) NOT NULL,
  firm_name VARCHAR(160) NULL,
  pan_number VARCHAR(10) NULL,
  aadhaar_number VARCHAR(12) NULL,
  gst_number VARCHAR(15) NULL,
  email VARCHAR(150) NULL,
  bank_details VARCHAR(255) NULL,
  state VARCHAR(80) NULL,
  district VARCHAR(80) NULL,
  tehsil VARCHAR(80) NULL,
  village VARCHAR(80) NULL,
  contact_person VARCHAR(120) NULL,
  contact_person_mobile VARCHAR(20) NULL,
  customer_type VARCHAR(40) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX(firm_name), INDEX(gst_number), INDEX(mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_attributes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  attr_key VARCHAR(60) NOT NULL,
  attr_value VARCHAR(255) NULL,
  UNIQUE KEY uq_cust_key (customer_id, attr_key),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  stopped_on DATE NULL,                       -- service stopped for this customer: no automatic jobs
  stop_reason VARCHAR(255) NULL,
  UNIQUE KEY uq_cust_service (customer_id, service_id),
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
  INDEX(customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Services master & file statuses =====
DROP TABLE IF EXISTS file_statuses;
DROP TABLE IF EXISTS services;

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE file_statuses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Service jobs (unified, config-driven) + children =====
DROP TABLE IF EXISTS service_job_credentials;
DROP TABLE IF EXISTS service_job_items;
DROP TABLE IF EXISTS service_payments;
DROP TABLE IF EXISTS service_jobs;

CREATE TABLE service_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  service_code VARCHAR(40) NOT NULL,         -- income_tax, gst, ...
  sub_type VARCHAR(40) NULL,                  -- itr/tds/audit/returns/...
  title VARCHAR(160) NULL,
  financial_year VARCHAR(9) NULL,
  period_label VARCHAR(30) NULL,
  due_date DATE NULL,
  due_date_2 DATE NULL,                       -- second due date (GSTR-3B of a combined GSTR-1 & 3B return)
  filing_date DATE NULL,
  completed_on DATE NULL,                     -- set automatically when the job is marked Completed
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  data JSON NULL,                             -- service-specific scalar fields
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  renewed_from_job_id BIGINT UNSIGNED NULL,   -- set on auto-created next-year ITR/TDS jobs
  auto_key VARCHAR(40) NULL,                  -- e.g. 'tds_24g:2026-04' for auto-created Form 24G jobs
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE INDEX uq_job_renewed_from (renewed_from_job_id),
  UNIQUE INDEX uq_job_auto_key (customer_id, auto_key),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (file_status_id) REFERENCES file_statuses(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX(customer_id), INDEX(service_code), INDEX(due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_mode ENUM('cash','bank') NOT NULL,
  received_date DATE NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  remarks VARCHAR(255) NULL,
  FOREIGN KEY (job_id) REFERENCES service_jobs(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id),
  INDEX(job_id), INDEX(received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- multi-row items (mutual fund investments, insurance types)
CREATE TABLE service_job_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  item_group VARCHAR(40) NOT NULL,      -- investment / insurance_type
  item_type VARCHAR(40) NULL,           -- sip/lumpsum/transfer | health/term...
  target_amount DECIMAL(14,2) NULL,
  achieved_amount DECIMAL(14,2) NULL,
  FOREIGN KEY (job_id) REFERENCES service_jobs(id) ON DELETE CASCADE,
  INDEX(job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- encrypted portal credentials (govt dept)
CREATE TABLE service_job_credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  cred_type VARCHAR(40) NOT NULL,       -- traces/it_portal/ain_24q/ain_26q/gst_portal
  username VARBINARY(512) NULL,
  password VARBINARY(512) NULL,
  FOREIGN KEY (job_id) REFERENCES service_jobs(id) ON DELETE CASCADE,
  INDEX(job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== System =====
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS activity_log;

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
  INDEX(user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_type ENUM('lead_followup','service_job') NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  remind_on DATE NOT NULL,
  channel ENUM('in_app') NOT NULL DEFAULT 'in_app',
  status ENUM('pending','sent') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  UNIQUE KEY uq_src (source_type, source_id, due_date),
  INDEX(remind_on, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(60) NOT NULL UNIQUE,
  setting_value VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(60) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  detail VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(entity, entity_id), INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ===== Families & family payment receipts =====
DROP TABLE IF EXISTS payment_receipts;
DROP TABLE IF EXISTS families;

CREATE TABLE payment_receipts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_id BIGINT UNSIGNED NOT NULL,
  payer_customer_id BIGINT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_mode ENUM('cash','bank') NOT NULL,
  received_date DATE NOT NULL,
  remarks VARCHAR(255) NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(family_id), INDEX(received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE families (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  contact_type ENUM('customer','lead') NULL,
  contact_id BIGINT UNSIGNED NULL,
  payer_customer_id BIGINT UNSIGNED NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE customers ADD COLUMN family_id BIGINT UNSIGNED NULL, ADD INDEX idx_customers_family (family_id);
ALTER TABLE leads     ADD COLUMN family_id BIGINT UNSIGNED NULL, ADD INDEX idx_leads_family (family_id);
ALTER TABLE service_payments ADD COLUMN receipt_id BIGINT UNSIGNED NULL, ADD INDEX idx_sp_receipt (receipt_id);

ALTER TABLE customers ADD CONSTRAINT fk_customers_family FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL;
ALTER TABLE leads     ADD CONSTRAINT fk_leads_family     FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL;
ALTER TABLE payment_receipts ADD CONSTRAINT fk_pr_family FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE;
ALTER TABLE service_payments ADD CONSTRAINT fk_sp_receipt FOREIGN KEY (receipt_id) REFERENCES payment_receipts(id) ON DELETE CASCADE;

-- ===== Customer-level payments & discounts =====
DROP TABLE IF EXISTS customer_ledger;
CREATE TABLE customer_ledger (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  entry_type ENUM('payment','discount') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_mode ENUM('cash','upi','bank') NULL,
  entry_date DATE NOT NULL,
  remarks VARCHAR(255) NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (customer_id), INDEX (entry_date),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Expenses & cash in hand =====
DROP TABLE IF EXISTS expenses;
CREATE TABLE expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(60) NOT NULL,
  other_name VARCHAR(80) NULL,                 -- typed name when the category is "Other"
  description VARCHAR(255) NULL,
  amount DECIMAL(12,2) NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (expense_date), INDEX (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS cash_adjustments;
CREATE TABLE cash_adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL,
  direction ENUM('in','out') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  remarks VARCHAR(255) NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Employee attendance =====
DROP TABLE IF EXISTS attendance;
CREATE TABLE attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  att_date DATE NOT NULL,
  status ENUM('present','half','absent','leave') NOT NULL,
  remarks VARCHAR(255) NULL,
  marked_by BIGINT UNSIGNED NOT NULL,
  marked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_att_user_day (user_id, att_date),
  INDEX (att_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Invoices =====
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(20) NOT NULL,
  fy_label VARCHAR(9) NOT NULL,
  seq INT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  group_id BIGINT UNSIGNED NULL,                -- set on a group invoice
  invoice_date DATE NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  notes VARCHAR(255) NULL,
  share_token VARCHAR(40) NULL,                -- private link /i/<token> used to share the invoice
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_no (fy_label, seq),
  UNIQUE KEY uq_invoice_share (share_token),
  INDEX (customer_id), INDEX (invoice_date), INDEX (group_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id) REFERENCES families(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE invoice_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  job_id BIGINT UNSIGNED NULL,
  member_name VARCHAR(120) NULL,                -- group invoices: whom the line is for
  particulars VARCHAR(255) NOT NULL,
  financial_year VARCHAR(9) NULL,
  amount DECIMAL(12,2) NOT NULL,
  INDEX (invoice_id), INDEX (job_id),
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
