-- Auto-generated monthly Form 24G jobs: stable key 'tds_24g:YYYY-MM' (return month), one per customer per month.
ALTER TABLE service_jobs ADD COLUMN auto_key VARCHAR(40) NULL,
  ADD UNIQUE INDEX uq_job_auto_key (customer_id, auto_key);
