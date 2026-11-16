-- Expense management & cash in hand.
-- Cash in hand = opening balance
--              + CASH payments received from customers (customer_ledger / job payments with mode 'cash')
--              + money added to cash  - money taken out of cash  - expenses,
-- counted from the cash-book start date (settings: cash_book_start, cash_opening).

CREATE TABLE IF NOT EXISTS expenses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  amount DECIMAL(12,2) NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (expense_date), INDEX (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Manual cash movements that are not expenses or customer receipts (owner adds cash, cash deposited to bank, ...)
CREATE TABLE IF NOT EXISTS cash_adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_date DATE NOT NULL,
  direction ENUM('in','out') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  remarks VARCHAR(255) NOT NULL,
  recorded_by BIGINT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permission: Admin + Manager
INSERT INTO permissions (name, slug) SELECT 'Manage expenses & cash in hand', 'expenses.manage'
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE slug = 'expenses.manage');
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug = 'expenses.manage' WHERE r.slug IN ('admin', 'manager');
