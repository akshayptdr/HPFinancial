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
  filing_date DATE NULL,
  file_status_id BIGINT UNSIGNED NULL,
  fees_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  comment TEXT NULL,
  data JSON NULL,                             -- service-specific scalar fields
  assigned_to BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

SET FOREIGN_KEY_CHECKS=1;
