-- GST returns: second due date for the combined GSTR-1 & GSTR-3B return (GSTR-3B due date).
ALTER TABLE service_jobs ADD COLUMN due_date_2 DATE NULL AFTER due_date;
