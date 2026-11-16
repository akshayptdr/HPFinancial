-- HP Financial — FULL DEPLOY (schema + seed)
-- Run this once on a fresh database via phpMyAdmin.
-- Login after import: Mobile 9999999999 / Password admin123

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
DROP TABLE IF EXISTS lead_category_map;
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
  address TEXT NULL,
  referred_by VARCHAR(120) NULL,
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
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX(status), INDEX(assigned_to), INDEX(follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lead_category_map (
  lead_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (lead_id, category_id),
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES lead_categories(id) ON DELETE CASCADE
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
DROP TABLE IF EXISTS customer_activities;
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
  address TEXT NULL,
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

CREATE TABLE customer_activities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  type ENUM('note','call','meeting') NOT NULL DEFAULT 'note',
  description TEXT NULL,
  follow_up_at DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

-- ===== Service jobs =====
DROP TABLE IF EXISTS service_job_credentials;
DROP TABLE IF EXISTS service_job_items;
DROP TABLE IF EXISTS service_payments;
DROP TABLE IF EXISTS service_jobs;

CREATE TABLE service_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  service_code VARCHAR(40) NOT NULL,
  sub_type VARCHAR(40) NULL,
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
  data JSON NULL,
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
  INDEX(customer_id), INDEX(service_code), INDEX(due_date), INDEX(financial_year)
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

CREATE TABLE service_job_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  item_group VARCHAR(40) NOT NULL,
  item_type VARCHAR(40) NULL,
  target_amount DECIMAL(14,2) NULL,
  achieved_amount DECIMAL(14,2) NULL,
  FOREIGN KEY (job_id) REFERENCES service_jobs(id) ON DELETE CASCADE,
  INDEX(job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE service_job_credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  cred_type VARCHAR(40) NOT NULL,
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

-- =============================================
-- SEED DATA
-- =============================================

-- Roles
INSERT INTO roles (id, name, slug) VALUES
 (1,'Admin','admin'), (2,'Employee','employee'), (3,'Manager','manager');

-- Permissions
INSERT INTO permissions (id, name, slug) VALUES
 (1,'View employees','employees.view'),
 (2,'Manage employees','employees.manage'),
 (3,'Manage roles','roles.manage'),
 (4,'View leads','leads.view'),
 (5,'Create leads','leads.create'),
 (6,'Edit leads','leads.edit'),
 (7,'View customers','customers.view'),
 (8,'Create customers','customers.create'),
 (9,'Edit customers','customers.edit'),
 (10,'View services','services.view'),
 (11,'Edit services','services.edit'),
 (12,'Record payments','payments.record'),
 (13,'View reports','reports.view'),
 (14,'Manage settings','settings.manage'),
 (15,'Manage masters','masters.manage'),
 (16,'Give discounts / write-offs','payments.discount'),
 (17,'Manage expenses & cash in hand','expenses.manage'),
 (18,'Manage attendance','attendance.manage'),
 (19,'Create & print invoices','invoices.manage');

-- Role permissions: admin = all
INSERT INTO role_permissions (role_id, permission_id)
 SELECT 1, id FROM permissions;
-- employee = operational subset
INSERT INTO role_permissions (role_id, permission_id) VALUES
 (2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13);
-- manager = same starting set as employee (tune in Roles & Access)
INSERT INTO role_permissions (role_id, permission_id) SELECT 3, permission_id FROM role_permissions WHERE role_id = 2;
-- discounts / write-offs: Manager (Admin already has every permission)
INSERT INTO role_permissions (role_id, permission_id) VALUES (3, 16), (3, 17), (3, 18), (3, 19);

-- Admin user  →  Mobile: 9999999999 / Password: admin123
INSERT INTO users (id, name, mobile, email, password_hash, role_id, status, must_change_password)
VALUES (1,'Administrator','9999999999','admin@hpfinancial.local',
 '$2y$10$DnyiNfIyPNMX78NPXwTZyeqJXSOcRBxbxt5fjN/sqZPBM5Cm6MdbK',1,'active',0);

-- Services (10)
INSERT INTO services (name, code) VALUES
 ('Income Tax','income_tax'),
 ('GST','gst'),
 ('Accounting','accounting'),
 ('Loan & Subsidy','loan_subsidy'),
 ('Mutual Fund','mutual_fund'),
 ('Insurance','insurance'),
 ('Certificate & Other Audit','certificate'),
 ('LLP Compliances','llp_compliance'),
 ('Company Compliances','company_compliance');

-- File statuses
INSERT INTO file_statuses (name, sort_order) VALUES
 ('In-Progress',1),('Completed',2),('Discontinued',3);

-- Lead masters
INSERT INTO lead_types (name) VALUES ('Referral'),('Social Media'),('Walk-in'),('Phone Call');
INSERT INTO lead_categories (name) VALUES
 ('Income Tax'),('GST'),('Accounting'),('Loan & Subsidy'),('Mutual Fund'),
 ('Insurance'),('Certificate & Other Audit'),('LLP Compliances'),('Company Compliances');

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES ('reminder_lead_days','3');

-- Invoice details (editable under Settings); defaults come from the sample invoice
INSERT INTO settings (setting_key, setting_value) VALUES
 ('inv_firm_name','HIMANSHU PATIDAR & CO.'),
 ('inv_firm_address','Anjad Naka, Sai Mandir Road, Barwani, M.P. - 451551'),
 ('inv_firm_phone','7046739577, 9179783044'),
 ('inv_firm_email','hpandco.ca@gmail.com'),
 ('inv_firm_pan','DQLPP6945M'),
 ('inv_upi','7046739577-2@ybl'),
 ('inv_acc_name','HIMANSHU PATIDAR'),
 ('inv_acc_type','Savings'),
 ('inv_acc_no','6803000100074926'),
 ('inv_bank','PUNJAB NATIONAL BANK (BARWANI)'),
 ('inv_ifsc','PUNB0680300'),
 ('inv_signatory','CA Himanshu Patidar'),
 ('inv_tagline','Chartered Accountants'),
 ('inv_frn',''),
 ('inv_terms',''),
 ('inv_logo',''),
 ('inv_show_ca_logo','1'),
 ('inv_whatsapp','8643043584'),
 ('inv_public_url',''),
 ('inv_last_fy','26-27'),
 ('inv_last_seq','120');

