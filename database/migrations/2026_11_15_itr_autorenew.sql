-- Auto-created next-year ITR jobs: link each one to the job it was renewed from.
-- UNIQUE guarantees a job can only ever be renewed once (no duplicates).
ALTER TABLE service_jobs ADD COLUMN renewed_from_job_id BIGINT UNSIGNED NULL,
  ADD UNIQUE INDEX uq_job_renewed_from (renewed_from_job_id);
