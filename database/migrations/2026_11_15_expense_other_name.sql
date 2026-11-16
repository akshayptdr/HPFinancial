-- Custom name for expenses whose category is "Other".
ALTER TABLE expenses ADD COLUMN other_name VARCHAR(80) NULL AFTER category;
