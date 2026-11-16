-- Leads: contact person's mobile number (the app already saves it; older installs were missing the column).
ALTER TABLE leads ADD COLUMN contact_person_mobile VARCHAR(20) NULL AFTER contact_person;
