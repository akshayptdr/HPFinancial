# HP Financial — CA Practice Management Software
## Project Requirement Document (PRD)

| | |
|---|---|
| **Document** | Project Requirement Document (PRD) |
| **Product** | HP Financial — CA Practice Management Software |
| **Version** | 1.0 (Phase 1) |
| **Date** | 2026-06-25 |
| **Owner** | it@farmkart.com |
| **Status** | Draft for review |

> **Document workflow:** PRD (this document) → **Technical Design Document**
> (`TECHNICAL_DESIGN.md`) → **Development**. No code is written until both
> documents are approved.

---

## 1. Overview

HP Financial is an internal web application for a Chartered Accountant (CA) firm
to run its day-to-day practice in one secure place. Today this work — capturing
enquiries (leads), onboarding clients, and tracking statutory/financial work
(ITR, GST, etc.) with their fees — is scattered across spreadsheets, WhatsApp,
and memory. This product centralizes it with employee logins, role-based access,
a lead-to-customer pipeline, ten service workflows, fee collection tracking, a
dashboard, reports, and reminders.

### 1.1 Goals
- Single source of truth for leads, customers, services, and fees.
- Clear ownership: every lead/job is assigned to an employee.
- Never miss a filing/follow-up: due dates, reminders, and overdue alerts.
- Full money trail: fees agreed, part-payments, mode (cash/bank), and who
  recorded each receipt.
- Secure handling of sensitive client and government-portal data.

### 1.2 Non-goals (Phase 1)
Client-facing portal, invoice/receipt PDF generation, WhatsApp/SMS messaging,
two-factor authentication, multi-branch operations. (See §10.)

---

## 2. Users & Roles

| Role | Description | Access |
|---|---|---|
| **Admin** | Firm owner / manager | Full access: manage employees, masters, all leads/customers/jobs, fees, reports, settings. |
| **Employee** | Staff member | Operational access: work on assigned leads/customers/jobs, record fees on own jobs, view permitted lists. Cannot manage employees or system masters. |

- Login identifier is the **mobile number**; access is governed by **role-based
  access control (RBAC)**, with a granular permission layer for future roles.
- Visibility of records and actions follows RBAC throughout (lists, buttons,
  pages, and reports).

---

## 3. Functional Requirements

### 3.1 Employee Login & Management
- **FR-1.1** Employees log in with **mobile number + password**.
- **FR-1.2** Login is blocked for **inactive** employees ("account disabled").
- **FR-1.3** Admin can **add an employee** with: name, mobile (unique = login
  ID), employee type (admin/employee), status (active/inactive), email
  (optional).
- **FR-1.4** On employee creation the system **auto-generates a password**,
  shown once to the admin to share; the employee must change it on first login.
- **FR-1.5** Admin can **reset an employee's password** from the CRM
  (re-generates a one-time password; forces change on next login).
- **FR-1.6** Admin can **edit** employee details and **toggle active/inactive**.
- **FR-1.7** Employees can change their **own password** from their profile.

### 3.2 Lead Management
- **FR-2.1** Capture a lead with: **Lead Type** (dropdown), **Name**, **Mobile**,
  **State**, **District**, **Tehsil**, **Village**, **Interested Category**
  (dropdown), **Contact Person**, **Follow-up Date** (optional).
- **FR-2.2** **Lead Type** and **Interested Category** dropdown options are
  **admin-managed** (add/edit without code changes).
- **FR-2.3** Leads can be **assigned** to employees.
- **FR-2.4** Lead list supports **search/filter** (type, category, status,
  assignee, follow-up date).
- **FR-2.5** Lead detail page shows an **activity/follow-up timeline**
  (notes, calls, meetings, status changes).
- **FR-2.6** Follow-up dates feed the dashboard "follow-ups due".

### 3.3 Customer Management
- **FR-3.1** **Convert Lead → Customer**, carrying over name, mobile, state,
  district, tehsil, village, and contact person; the source lead is marked
  *won*. Customers can also be created directly.
- **FR-3.2** Customer **profile** shows carried-over details plus editable
  fields: **Firm name, PAN number, Aadhaar number, GST number, Email,
  Bank/passbook** — the document-backed fields (PAN, Aadhaar, Bank/passbook) are
  captured as a **text value + optional document upload**.
- **FR-3.3** Uploaded documents are validated (type/size) and stored outside the
  public web root.
- **FR-3.4** **Shared "capture-once" fields:** **Firm name, GST number, PAN, and
  Aadhaar** are stored **once per customer** and **reused across all services**.
  They are **edited only on the customer profile** and shown **read-only** on
  every service form (never re-typed per service). The model is extensible so
  more shared fields can be added later without schema changes.
- **FR-3.5** A customer can be assigned **one or multiple services** from the
  service master; assigned services appear as sections/tabs on the profile.
- **FR-3.6** Customer list supports search/filter (service, status, assignee,
  location).

### 3.4 Services (10 services)
All services share a common **fees pattern** (§3.5) and an **admin-editable File
Status** master. The **shared capture-once fields** (Firm name, GST number, PAN,
Aadhaar — §3.3 / FR-3.4) appear **read-only** wherever a service needs them.
Each service has its own fields/sub-types:

**(1) Income Tax** — sub-types **ITR, TDS, Audit, Other**.
- ITR / Audit: financial year, due date, file status, fees.
- TDS: **form type (24Q / 26Q)**, financial year, due date, file status, fees.
- Other: free-text title + financial year, due date, file status, comment, fees.

**(2) GST** — three categories:
- *Returns*: GST profile (GST number, user ID, form name, **filing type
  Monthly/Quarterly**); Monthly = **GSTR-1 (due 10th)** and **GSTR-3B (due
  20th)**; Quarterly = same forms per quarter. Each return: due date, filing
  date, file status, fees.
- *Registration*: firm name, **TRN**, **ARN**, GST number, user ID, file status
  (Submitted / GST Number Received), comment, fees.
- *Audit*: **form type (9 / 9 & 9C)**, financial year, due date, file status,
  comment, fees.

**(3) Accounting** — **frequency (Monthly/Quarterly/Yearly)**, firm name,
financial year, **due date (default 5th)**, file status, fees.

**(4) Loan & Subsidy** — sub-types:
- *Udyam Registration*: firm name, due date, status, fees.
- *CMA Report* / *Loan*: firm name, bank name, loan amount, loan officer name,
  loan officer number, comment, loan status, due date, fees.

**(5) Mutual Fund** — account open? (Yes/No → **UCC number**); **investment type
multi-select (SIP / Lump Sum / Transfer)** with **target & achieved amount per
type**; due date, status, comment, fees.

**(6) Insurance** — **insurance type multi-select (Health/Term/Vehicle/Claim
Assist)**; referred by, company name, **policy type (Fresh/Port)**, status,
comment, fees. If **Claim Assist**: + company name, claim amount, follow-up
date, comment.

**(7) Government Department** — sub-types:
- *Audit*: firm name, officer name, officer contact, audit type, financial year,
  due date, fees.
- *GST*: firm name, officer name, office contact, user ID, password, **due date
  (default 10th)**, **form type (GSTR-7/other)**, status, comment, financial
  year, GST number, fees.
- *TDS*: **form type (24G/26Q/24R)**, form name, officer name, contact person,
  officer contact, **TAN number**, with **conditional credentials**: TRACES
  ID/password + IT username/password (if TAN), AIN-24Q ID/password, AIN-26Q
  ID/password; due date, status, comment, fees.
- **All stored portal credentials are encrypted at rest** (§6).

**(8) Certificate** — form name, certificate type, comment, status, due date,
fees.

**(9) Deeds & Agreement** — firm name, type, status, due date, fees.

**(10) Company Compliances** — sub-types **Audit** and **Company Registration**
(Phase 1); *Compliances* and *Other* (Phase 2).
- Audit: form name, status, due date, financial year, fees.
- Company Registration: firm name, **CS number, CS contact number, CS name**,
  status, due date, fees.

**Firm-wide work board** — a unified view across all service jobs with filters
(service, file status, assignee, due-date range, financial year) and
**overdue / due-soon** highlighting.

### 3.5 Fees Collection (shared across all services)
- **FR-5.1** Each service job has a total **agreed fee** and a **comment**.
- **FR-5.2** Fees can be collected as **multiple part-payments**; each receipt
  records **amount, payment mode (Cash/Bank), received date, remarks**, and
  **who recorded it** with timestamp.
- **FR-5.3** The system computes **received total** and **outstanding balance**.
- **FR-5.4** Payments can be recorded/edited by **admin or the job's assigned
  employee**; every change is **audit-logged**.

### 3.6 Dashboard
- **FR-6.1 (Admin)** totals (leads by status, customers, active services), fees
  collected vs pending, due-soon & overdue jobs, follow-ups due, recent
  activity, employee workload.
- **FR-6.2 (Employee)** my leads, my follow-ups due today, my due-soon/overdue
  jobs, fees I collected.

### 3.7 Reports & Exports
Filterable reports with **CSV/Excel export**:
- **FR-7.1** Leads (by type/category/source/status/assignee/date; conversion).
- **FR-7.2** Customers (by service/location/assignee).
- **FR-7.3** Per-service / job (status, due-date range, assignee, financial year).
- **FR-7.4** Fees/collection (collected, outstanding, by mode/service/employee/
  date).
- **FR-7.5** Employee performance (jobs handled, fees collected, conversions).

### 3.8 Reminders & Notifications
- **FR-8.1** In-app **notification center** (assignments, due-date alerts,
  follow-ups, status changes).
- **FR-8.2** **Due-date reminders** with configurable lead time (N days before),
  generated by a **daily scheduled task** for service-job due dates and lead
  follow-ups.
- **FR-8.3** Phase 1 is **in-app notifications only**. **Email, WhatsApp, and
  SMS reminders are Phase 2** (no email reminders in Phase 1).

### 3.9 Audit Log
- **FR-9.1** Key create/update/delete actions and **all fee/payment changes**
  are logged with **who + when**, viewable by admin.

---

## 4. Admin-managed Masters
Admins maintain dropdown/option lists without developer involvement:
Lead Types, Interested Categories, Services, File Statuses, Insurance Types, and
app settings (reminder lead-time days).

---

## 5. Non-Functional Requirements
- **Platform:** Core PHP (no framework), PDO + MySQL/MariaDB, Bootstrap 5 UI.
- **Hosting:** Hostinger shared hosting, PHP 8.1+, HTTPS (free SSL).
- **Scale:** 1–15 internal users.
- **Usability:** clean, responsive back-office UI; fast common actions.
- **Maintainability:** MVC structure, masters editable in-app, schema extensible
  for Phase 2 modules without rewrite.
- **Reliability:** daily reminder job; database backups (operational).

---

## 6. Security Requirements
- Login by mobile + password; passwords stored with **bcrypt** (`password_hash`).
- **Sessions** regenerated on login; secure + httponly cookies; idle timeout.
- **RBAC** enforced server-side (middleware) and reflected in the UI.
- **CSRF** tokens on all state-changing forms.
- **Output escaping** (anti-XSS) and **prepared statements** (anti-SQL-injection)
  everywhere.
- **Document uploads** validated and stored outside the web root.
- **Government/third-party portal credentials** (TRACES, IT, GST, AIN) are
  **encrypted at rest**; visible only to admin + assigned employee.
- Only the `public/` directory is web-exposed; app/config/storage/.env denied.

---

## 7. Data Entities (summary)
Employees/Users, Roles, Permissions; Lead Types, Interested Categories, Leads,
Lead Activities; Customers, **Customer Attributes** (shared capture-once fields),
Customer Services, Customer Documents; Services, File Statuses; one **job table
per service** (+ GST profile, Mutual Fund investments, Insurance types, Govt Dept
credentials); **Service Payments** (shared, part-payments); Notifications,
Reminders, Settings, Activity Log.
*(Full schema/DDL in the Technical Design Document.)*

---

## 8. Assumptions
- Internal-only application (no public/client login in Phase 1).
- One firm / single branch.
- Hostinger supports PHP 8.1+, Composer, and cron for the daily reminder job.
- GSTR-1 monthly default due date captured as the **10th** (editable); to be
  confirmed against the firm's practice (statutory date is the 11th).

---

## 9. Open Items to Confirm
- Government Dept TDS **"24R"** form type — confirm exact form.

---

## 10. Phase 2 / Future Scope
- Company Compliances sub-types **Compliances** and **Other**.
- **Email, WhatsApp & SMS** reminders and OTP.
- Client-facing portal; invoice/receipt PDF generation; two-factor auth;
  multi-branch.

---

## 11. Acceptance (high level)
Phase 1 is accepted when an admin can: create employees and log in by mobile;
capture and assign leads; convert leads to customers and upload documents; assign
services and create jobs across all 10 services; record part-payments with the
correct received/outstanding totals and audit trail; view the dashboard and run
reports with CSV export; and receive **in-app** due-date/follow-up reminders —
all over HTTPS with the security controls in §6 verified.
