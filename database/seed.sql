-- HP Financial — seed data. Run after schema.sql on DB `hp`.
SET NAMES utf8mb4;

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
 ('Certificate & Other Audit','certificate'),
 ('LLP Compliances','llp_compliance'),
 ('Company Compliances','company_compliance');

-- File statuses
INSERT INTO file_statuses (name, sort_order) VALUES
 ('Pending',1), ('In-Progress',2), ('Filed',3), ('Completed',4), ('Discontinued',5);

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

