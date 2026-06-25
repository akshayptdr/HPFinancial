-- HP Financial — seed data. Run after schema.sql on DB `hp`.
SET NAMES utf8mb4;

-- Roles
INSERT INTO roles (id, name, slug) VALUES
 (1,'Admin','admin'), (2,'Employee','employee');

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
 (15,'Manage masters','masters.manage');

-- Role permissions: admin = all
INSERT INTO role_permissions (role_id, permission_id)
 SELECT 1, id FROM permissions;
-- employee = operational subset
INSERT INTO role_permissions (role_id, permission_id) VALUES
 (2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13);

-- Admin user (mobile 9999999999 / password admin123 ; must change on first login)
INSERT INTO users (id, name, mobile, email, password_hash, role_id, status, must_change_password)
VALUES (1,'Administrator','9999999999','admin@hpfinancial.local',
 '$2y$10$w5LksoLA0rNZdCpTgThbH.m4jL57y1VQmsiL3H7sBsYJv.H6iQnza',1,'active',1);

-- Services master (10)
INSERT INTO services (name, code) VALUES
 ('Income Tax','income_tax'),
 ('GST','gst'),
 ('Accounting','accounting'),
 ('Loan & Subsidy','loan_subsidy'),
 ('Mutual Fund','mutual_fund'),
 ('Insurance','insurance'),
 ('Government Department','govt_dept'),
 ('Certificate','certificate'),
 ('Deeds & Agreement','deeds_agreement'),
 ('Company Compliances','company_compliance');

-- File statuses
INSERT INTO file_statuses (name, sort_order) VALUES
 ('Pending',1), ('In-Progress',2), ('Filed',3), ('Completed',4);

-- Lead masters
INSERT INTO lead_types (name) VALUES ('Referral'),('Social Media'),('Walk-in'),('Phone Call');
INSERT INTO lead_categories (name) VALUES
 ('Income Tax'),('GST'),('Accounting'),('Loan & Subsidy'),('Mutual Fund'),
 ('Insurance'),('Government Department'),('Certificate'),('Deeds & Agreement'),('Company Compliances');

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES ('reminder_lead_days','3');
