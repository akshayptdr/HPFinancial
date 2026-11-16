-- Stop a service for a customer: no more automatic jobs, open jobs become "Discontinued".
ALTER TABLE customer_services
  ADD COLUMN stopped_on DATE NULL,
  ADD COLUMN stop_reason VARCHAR(255) NULL;

-- "Discontinued" file status: like Completed, it removes a job from the Work Board and from reminders.
INSERT INTO file_statuses (name, sort_order)
  SELECT 'Discontinued', 5 WHERE NOT EXISTS (SELECT 1 FROM file_statuses WHERE name = 'Discontinued');
