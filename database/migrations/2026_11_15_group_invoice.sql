-- Group invoices: one invoice listing jobs of several members of a group.
-- invoices.customer_id stays the "billed to" person (e.g. the group's payer); group_id marks it as a group invoice;
-- each line remembers which member it is for (member_name snapshot) so it can be printed next to the service.
ALTER TABLE invoices
  ADD COLUMN group_id BIGINT UNSIGNED NULL AFTER customer_id,
  ADD INDEX idx_invoices_group (group_id),
  ADD CONSTRAINT fk_invoices_group FOREIGN KEY (group_id) REFERENCES families(id) ON DELETE SET NULL;
ALTER TABLE invoice_items ADD COLUMN member_name VARCHAR(120) NULL AFTER job_id;
