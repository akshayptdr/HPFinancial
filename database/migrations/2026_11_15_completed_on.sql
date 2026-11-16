-- Date a job was marked Completed (set automatically). Drives Insurance policy renewals.
ALTER TABLE service_jobs ADD COLUMN completed_on DATE NULL AFTER filing_date;
