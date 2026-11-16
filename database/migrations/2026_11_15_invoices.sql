-- Customer invoices: pick jobs -> one invoice, one line per job. Bill numbers like 26-27/121, restarting each financial year.

CREATE TABLE IF NOT EXISTS invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(20) NOT NULL,                 -- e.g. 26-27/121
  fy_label VARCHAR(9) NOT NULL,                    -- e.g. 26-27
  seq INT UNSIGNED NOT NULL,                       -- 121
  customer_id BIGINT UNSIGNED NOT NULL,
  invoice_date DATE NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  notes VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_no (fy_label, seq),
  INDEX (customer_id), INDEX (invoice_date),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  job_id BIGINT UNSIGNED NULL,                     -- the job this line bills (kept loose so deleting a job never breaks an invoice)
  particulars VARCHAR(255) NOT NULL,
  financial_year VARCHAR(9) NULL,
  amount DECIMAL(12,2) NOT NULL,
  INDEX (invoice_id), INDEX (job_id),
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permission: create / print invoices (Admin + Manager)
INSERT INTO permissions (name, slug) SELECT 'Create & print invoices', 'invoices.manage'
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE slug = 'invoices.manage');
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug = 'invoices.manage' WHERE r.slug IN ('admin', 'manager');

-- Firm / bank details printed on every invoice (editable under Settings -> Invoice details).
-- Defaults are taken from the sample invoice; inv_last_* = the last bill number already used (26-27/120).
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
 ('inv_firm_name',    'HIMANSHU PATIDAR & CO.'),
 ('inv_firm_address', 'Anjad Naka, Sai Mandir Road, Barwani, M.P. - 451551'),
 ('inv_firm_phone',   '7046739577, 9179783044'),
 ('inv_firm_email',   'hpandco.ca@gmail.com'),
 ('inv_firm_pan',     'DQLPP6945M'),
 ('inv_upi',          '7046739577-2@ybl'),
 ('inv_acc_name',     'HIMANSHU PATIDAR'),
 ('inv_acc_type',     'Savings'),
 ('inv_acc_no',       '6803000100074926'),
 ('inv_bank',         'PUNJAB NATIONAL BANK (BARWANI)'),
 ('inv_ifsc',         'PUNB0680300'),
 ('inv_signatory',    'CA Himanshu Patidar'),
 ('inv_last_fy',      '26-27'),
 ('inv_last_seq',     '120');
