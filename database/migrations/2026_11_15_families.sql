-- Families / households + family payment receipts (split across members' jobs)
-- Safe to run once on an existing database.

CREATE TABLE IF NOT EXISTS payment_receipts (
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

CREATE TABLE IF NOT EXISTS families (
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
