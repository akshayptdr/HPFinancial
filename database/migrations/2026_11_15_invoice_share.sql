-- Share an invoice on WhatsApp: every invoice gets a private, unguessable link (/i/<token>) that opens a read-only copy
-- without logging in. The token can be reset to kill an old link.
ALTER TABLE invoices ADD COLUMN share_token VARCHAR(40) NULL, ADD UNIQUE INDEX uq_invoice_share (share_token);
UPDATE invoices SET share_token = LEFT(SHA2(CONCAT(UUID(), RAND(), id), 256), 32) WHERE share_token IS NULL;

-- WhatsApp number shown in the message ("for queries, WhatsApp us on ..."), and the website address used in the link
-- (leave blank to use the address the app is opened on; set e.g. https://cahpgroup.in once the app is online).
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
 ('inv_whatsapp',   '8643043584'),
 ('inv_public_url', '');
