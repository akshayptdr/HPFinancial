-- Invoice branding: tagline, ICAI firm registration number, terms line and logo (file name under storage/uploads/invoice/).
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
 ('inv_tagline', 'Chartered Accountants'),
 ('inv_frn',     ''),
 ('inv_terms',   ''),
 ('inv_logo',    ''),
 ('inv_show_ca_logo', '1');   -- print the ICAI "CA" logo on invoices
