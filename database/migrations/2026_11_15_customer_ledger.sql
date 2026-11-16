-- Customer-level payments & discounts (no per-job split).
-- Outstanding = SUM(job fees) - SUM(job-level payments, legacy) - SUM(ledger payments) - SUM(ledger discounts).

CREATE TABLE IF NOT EXISTS customer_ledger (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  entry_type ENUM('payment','discount') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_mode ENUM('cash','upi','bank') NULL,          -- payments only
  entry_date DATE NOT NULL,
  remarks VARCHAR(255) NULL,                             -- required for discounts
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (customer_id), INDEX (entry_date),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- New permission: give discounts / write-offs (Admin + Manager)
INSERT INTO permissions (name, slug) SELECT 'Give discounts / write-offs', 'payments.discount'
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE slug = 'payments.discount');
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug = 'payments.discount' WHERE r.slug IN ('admin', 'manager');

-- Earlier family receipts: the part that was kept as an unallocated advance now belongs to the payer's own ledger
-- (the allocated parts already count as the members' job payments).
INSERT INTO customer_ledger (customer_id, entry_type, amount, payment_mode, entry_date, remarks, recorded_by, recorded_at)
SELECT COALESCE(r.payer_customer_id, (SELECT MIN(c.id) FROM customers c WHERE c.family_id = r.family_id)),
       'payment',
       r.amount - COALESCE((SELECT SUM(sp.amount) FROM service_payments sp WHERE sp.receipt_id = r.id), 0),
       r.payment_mode, r.received_date,
       CONCAT('Advance from family receipt #', r.id), r.recorded_by, r.recorded_at
FROM payment_receipts r
WHERE r.amount - COALESCE((SELECT SUM(sp.amount) FROM service_payments sp WHERE sp.receipt_id = r.id), 0) > 0.004
  AND COALESCE(r.payer_customer_id, (SELECT MIN(c.id) FROM customers c WHERE c.family_id = r.family_id)) IS NOT NULL;
