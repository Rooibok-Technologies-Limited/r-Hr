--
-- Rooibok HR System — Archive Database Schema (Tier 2)
-- Phase 10: Data Archive Subsystem
--

-- Master company snapshot — one row per archived company event
CREATE TABLE arc_company_snapshots (
    snapshot_id         SERIAL PRIMARY KEY,
    source_company_id   INTEGER NOT NULL,
    company_name        VARCHAR(200),
    trading_name        VARCHAR(200),
    admin_first_name    VARCHAR(100),
    admin_last_name     VARCHAR(100),
    admin_email         VARCHAR(200),
    admin_phone         VARCHAR(30),
    country             VARCHAR(100),
    city                VARCHAR(100),
    region              VARCHAR(100),
    company_type        VARCHAR(100),
    registration_no     VARCHAR(100),
    employee_count      INTEGER,
    plan_name           VARCHAR(100),
    plan_tier           VARCHAR(20),
    subscription_start  DATE,
    subscription_end    DATE,
    total_months_paid   INTEGER,
    total_revenue_ugx   NUMERIC(14,2),
    cancellation_reason VARCHAR(200),
    archive_reason      VARCHAR(50),
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    vault_bundle_path   VARCHAR(500),
    vault_checksum      VARCHAR(64),
    consent_given       SMALLINT DEFAULT 0,
    consent_date        TIMESTAMP WITH TIME ZONE,
    unsubscribed        SMALLINT DEFAULT 0,
    unsubscribed_at     TIMESTAMP WITH TIME ZONE
);

-- Archived employees
CREATE TABLE arc_employees (
    arc_id              SERIAL PRIMARY KEY,
    source_record_id    INTEGER NOT NULL,
    source_company_id   INTEGER NOT NULL,
    snapshot_id         INTEGER REFERENCES arc_company_snapshots(snapshot_id),
    first_name          VARCHAR(100),
    last_name           VARCHAR(100),
    email               VARCHAR(200),
    phone               VARCHAR(30),
    department          VARCHAR(100),
    designation         VARCHAR(100),
    employment_type     VARCHAR(50),
    date_joined         DATE,
    date_left           DATE,
    leaving_reason      VARCHAR(200),
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    archive_reason      VARCHAR(50)
);

-- Archived attendance (age-based rotation)
CREATE TABLE arc_attendance (
    arc_id              SERIAL PRIMARY KEY,
    source_record_id    INTEGER NOT NULL,
    source_company_id   INTEGER NOT NULL,
    employee_id         INTEGER,
    employee_name       VARCHAR(200),
    attendance_date     DATE,
    clock_in            TIMESTAMP WITH TIME ZONE,
    clock_out           TIMESTAMP WITH TIME ZONE,
    total_work          VARCHAR(10),
    attendance_status   VARCHAR(20),
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Archived payroll (age-based rotation)
CREATE TABLE arc_payroll (
    arc_id              SERIAL PRIMARY KEY,
    source_record_id    INTEGER NOT NULL,
    source_company_id   INTEGER NOT NULL,
    employee_id         INTEGER,
    employee_name       VARCHAR(200),
    payroll_month       VARCHAR(7),
    gross_salary        NUMERIC(12,2),
    paye_deduction      NUMERIC(12,2),
    nssf_employee       NUMERIC(12,2),
    nssf_employer       NUMERIC(12,2),
    net_pay             NUMERIC(12,2),
    currency            VARCHAR(10),
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Archived leave records
CREATE TABLE arc_leaves (
    arc_id              SERIAL PRIMARY KEY,
    source_record_id    INTEGER NOT NULL,
    source_company_id   INTEGER NOT NULL,
    employee_name       VARCHAR(200),
    leave_type          VARCHAR(100),
    start_date          DATE,
    end_date            DATE,
    days_taken          INTEGER,
    status              VARCHAR(20),
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Archived system logs
CREATE TABLE arc_system_logs (
    arc_id              SERIAL PRIMARY KEY,
    source_company_id   INTEGER,
    log_type            VARCHAR(50),
    description         TEXT,
    metadata            JSONB,
    log_date            TIMESTAMP WITH TIME ZONE,
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Marketing intelligence contacts
CREATE TABLE arc_contacts (
    contact_id          SERIAL PRIMARY KEY,
    snapshot_id         INTEGER REFERENCES arc_company_snapshots(snapshot_id),
    contact_type        VARCHAR(20) NOT NULL,
    first_name          VARCHAR(100),
    last_name           VARCHAR(100),
    full_name           VARCHAR(200),
    email               VARCHAR(200),
    phone               VARCHAR(30),
    company_name        VARCHAR(200),
    country             VARCHAR(100),
    city                VARCHAR(100),
    region              VARCHAR(100),
    industry            VARCHAR(100),
    employee_count      INTEGER,
    plan_tier           VARCHAR(20),
    subscription_months INTEGER,
    status              VARCHAR(20),
    last_seen           DATE,
    consent_given       SMALLINT DEFAULT 0,
    unsubscribed        SMALLINT DEFAULT 0,
    tags                TEXT[],
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for fast queries
CREATE INDEX idx_arc_snapshots_company ON arc_company_snapshots(source_company_id);
CREATE INDEX idx_arc_employees_company ON arc_employees(source_company_id);
CREATE INDEX idx_arc_attendance_company ON arc_attendance(source_company_id, attendance_date);
CREATE INDEX idx_arc_payroll_company ON arc_payroll(source_company_id, payroll_month);
CREATE INDEX idx_arc_contacts_region ON arc_contacts(country, city);
CREATE INDEX idx_arc_contacts_status ON arc_contacts(status, consent_given);
CREATE INDEX idx_arc_contacts_plan ON arc_contacts(plan_tier, employee_count);
CREATE INDEX idx_arc_contacts_industry ON arc_contacts(industry);
CREATE INDEX idx_arc_contacts_email ON arc_contacts(email);
