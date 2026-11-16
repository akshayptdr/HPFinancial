-- Employee attendance: one row per employee per day.
CREATE TABLE IF NOT EXISTS attendance (
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

-- Permission: mark / edit / see everyone's attendance (Admin + Manager). Every employee can see their own.
INSERT INTO permissions (name, slug) SELECT 'Manage attendance', 'attendance.manage'
  WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE slug = 'attendance.manage');
INSERT IGNORE INTO role_permissions (role_id, permission_id)
  SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug = 'attendance.manage' WHERE r.slug IN ('admin', 'manager');
