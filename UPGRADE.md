# Rooibok HR System — Master Upgrade Plan
**Document version:** 1.0  
**Prepared:** March 2026  
**System:** TimeHRM v3.0.1 → Rooibok HR System  
**Architecture:** CodeIgniter 4 · PHP 8.2 · PostgreSQL 16 · Docker · Redis · Beanstalkd  

---

## Table of Contents

- [Phase 0 — Environment Setup](#phase-0--environment-setup)
- [Phase 1 — Foundation](#phase-1--foundation)
- [Phase 2 — Security and Auth](#phase-2--security-and-auth)
- [Phase 3 — Payment Engine](#phase-3--payment-engine)
- [Phase 4 — Subscription Lifecycle](#phase-4--subscription-lifecycle)
- [Phase 5 — Landing Page, Demo and Compliance](#phase-5--landing-page-demo-and-compliance)
- [Phase 6 — Attendance Hardware](#phase-6--attendance-hardware)
- [Phase 7 — Printing and Final Polish](#phase-7--printing-and-final-polish)
- [Phase 8 — Full Post-Execution Audit](#phase-8--full-post-execution-audit)
- [Phase 9 — Test Phase](#phase-9--test-phase)
- [Database Reference](#database-reference)
- [Environment Variables Reference](#environment-variables-reference)

---

## Phase 0 — Environment Setup

**Priority:** Must complete before any other phase  
**Estimated time:** 4–7 days  
**Goal:** Replace bare-metal development with Docker, migrate from MySQL to PostgreSQL, bump PHP to 8.2. All subsequent phases are built inside this environment.

---

### 0a — Docker Compose Environment

#### Directory structure to create

```
rooibok-hr/
├── app/                          (CI4 application — unchanged)
├── public/                       (web root)
├── system/                       (CI4 core)
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── nginx/
│   │   └── nginx.conf
│   └── postgres/
│       └── init.sql              (converted schema — see 0b)
├── compose.yml
├── .env.dev
├── .env.prod
├── .env.example
└── .gitignore                    (must include .env.prod)
```

#### compose.yml

```yaml
services:

  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: rooibok_app
    restart: unless-stopped
    volumes:
      - .:/var/www/html
      - uploads_data:/var/www/html/public/uploads
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - rooibok_net
    environment:
      APP_ENV:     ${APP_ENV}
      DB_HOST:     postgres
      DB_PORT:     5432
      DB_NAME:     ${DB_NAME}
      DB_USER:     ${DB_USER}
      DB_PASS:     ${DB_PASS}
      REDIS_HOST:  redis
      BEANSTALK:   beanstalkd

  nginx:
    image: nginx:1.25-alpine
    container_name: rooibok_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html:ro
      - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf:ro
      - certbot_data:/etc/letsencrypt
    depends_on:
      - app
    networks:
      - rooibok_net

  postgres:
    image: postgres:16-alpine
    container_name: rooibok_postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB:       ${DB_NAME}
      POSTGRES_USER:     ${DB_USER}
      POSTGRES_PASSWORD: ${DB_PASS}
    volumes:
      - pg_data:/var/lib/postgresql/data
      - ./docker/postgres/init.sql:/docker-entrypoint-initdb.d/01_schema.sql
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USER} -d ${DB_NAME}"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - rooibok_net

  redis:
    image: redis:7-alpine
    container_name: rooibok_redis
    restart: unless-stopped
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
    volumes:
      - redis_data:/data
    networks:
      - rooibok_net

  beanstalkd:
    image: schickling/beanstalkd
    container_name: rooibok_queue
    restart: unless-stopped
    networks:
      - rooibok_net

  pgadmin:
    image: dpage/pgadmin4:latest
    container_name: rooibok_pgadmin
    restart: unless-stopped
    environment:
      PGADMIN_DEFAULT_EMAIL:    ${PGADMIN_EMAIL}
      PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASS}
    ports:
      - "5050:80"
    depends_on:
      - postgres
    networks:
      - rooibok_net
    profiles:
      - dev

  mailhog:
    image: mailhog/mailhog
    container_name: rooibok_mail
    restart: unless-stopped
    ports:
      - "8025:8025"
    networks:
      - rooibok_net
    profiles:
      - dev

volumes:
  pg_data:
  redis_data:
  uploads_data:
  certbot_data:

networks:
  rooibok_net:
    driver: bridge
```

#### docker/php/Dockerfile

```dockerfile
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libicu-dev libcurl4-openssl-dev libonig-dev \
    libzip-dev libpq-dev unzip git curl \
  && docker-php-ext-install \
    intl curl mbstring pdo pdo_pgsql pgsql zip \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN chown -R www-data:www-data /var/www/html
```

#### docker/php/php.ini

```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 120
memory_limit = 256M
display_errors = Off
log_errors = On
date.timezone = Africa/Kampala
```

#### Dev commands

```bash
# Start development environment (with pgAdmin + Mailhog)
docker compose --profile dev up -d

# Start production environment
docker compose up -d --build

# View logs
docker compose logs -f app

# Access PostgreSQL shell
docker exec -it rooibok_postgres psql -U rooibok_user -d rooibok_hr

# Run CI4 spark commands
docker exec -it rooibok_app php spark migrate

# Run cron jobs manually
docker exec -it rooibok_app php spark billing:check
```

---

### 0b — PostgreSQL Migration

#### Why switching now is the right time
This system is not yet in production. There is no live data to migrate. Converting the schema now avoids the only genuinely painful part of a MySQL-to-PostgreSQL migration — data transformation on a live system.

#### Schema conversion — every change required

| MySQL | PostgreSQL | Notes |
|-------|-----------|-------|
| `AUTO_INCREMENT` | `SERIAL` or `GENERATED ALWAYS AS IDENTITY` | On all primary keys |
| `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` | *(remove entirely)* | Not needed in PostgreSQL |
| `TINYINT(1)` | `SMALLINT` | App stores 0/1 anyway |
| `LONGTEXT` | `TEXT` | PostgreSQL TEXT is unlimited |
| `DATETIME` | `TIMESTAMP` | Use TIMESTAMP WITH TIME ZONE for attendance |
| `ENUM(...)` | `VARCHAR(50) CHECK (col IN (...))` | Simpler than CREATE TYPE |
| Backtick identifiers | Double-quote identifiers or none | Query builder handles this |
| `DEFAULT '0000-00-00'` | `DEFAULT NULL` | PostgreSQL rejects zero dates |
| `int(11)` | `INTEGER` | Length specifier ignored in PG |
| `varchar(255)` | `VARCHAR(255)` | Identical |

#### SQL function replacements in app code

```bash
# Run these greps across app/ to find every location needing a fix
grep -rn "DATE_FORMAT"    app/   # → TO_CHAR(column, 'format')
grep -rn "IFNULL"         app/   # → COALESCE(column, fallback)
grep -rn "GROUP_CONCAT"   app/   # → STRING_AGG(column, ',')
grep -rn "LIKE '"         app/Models/  # → review for ILIKE where case-insensitive needed
grep -rn "NOW()"          app/   # → NOW() works in both — no change
grep -rn "UNIX_TIMESTAMP" app/   # → EXTRACT(EPOCH FROM column)
grep -rn "STR_TO_DATE"    app/   # → TO_TIMESTAMP(string, format)
```

#### Common conversion examples

```sql
-- MySQL
SELECT DATE_FORMAT(attendance_date, '%d-%m-%Y') FROM ci_timesheet;

-- PostgreSQL
SELECT TO_CHAR(attendance_date, 'DD-MM-YYYY') FROM ci_timesheet;

-- MySQL
SELECT IFNULL(total_work, '00:00') FROM ci_timesheet;

-- PostgreSQL
SELECT COALESCE(total_work, '00:00') FROM ci_timesheet;

-- MySQL
SELECT GROUP_CONCAT(department_name) FROM ci_departments;

-- PostgreSQL
SELECT STRING_AGG(department_name, ',') FROM ci_departments;
```

#### Update CI4 database config

File: `app/Config/Database.php`

```php
public array $default = [
    'DSN'      => '',
    'hostname' => 'postgres',   // Docker service name
    'username' => '',           // loaded from .env
    'password' => '',           // loaded from .env
    'database' => '',           // loaded from .env
    'DBDriver' => 'Postgre',    // was MySQLi
    'DBPrefix' => '',
    'pConnect' => false,
    'DBDebug'  => (ENVIRONMENT !== 'production'),
    'charset'  => 'utf8',
    'DBCollat' => '',
    'swapPre'  => '',
    'encrypt'  => false,
    'compress' => false,
    'strictOn' => false,
    'failover' => [],
    'port'     => 5432,         // was 3306
];
```

#### New PostgreSQL-specific tables to create immediately

```sql
-- Attendance edit audit trail (new — Phase 2)
CREATE TABLE ci_attendance_audit (
    audit_id        SERIAL PRIMARY KEY,
    attendance_id   INTEGER NOT NULL,
    company_id      INTEGER NOT NULL,
    changed_by      INTEGER NOT NULL,
    field_changed   VARCHAR(50),
    old_value       TEXT,
    new_value       TEXT,
    changed_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Subscription invoices (new — Phase 4)
CREATE TABLE ci_subscription_invoices (
    invoice_id          SERIAL PRIMARY KEY,
    invoice_number      VARCHAR(30) UNIQUE NOT NULL,
    company_id          INTEGER NOT NULL,
    membership_id       INTEGER,
    amount              NUMERIC(12,2) NOT NULL,
    currency            VARCHAR(10) DEFAULT 'UGX',
    payment_method      VARCHAR(30),
    transaction_ref     VARCHAR(200),
    pdf_path            VARCHAR(300),
    issued_at           TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    status              VARCHAR(20) DEFAULT 'paid'
);

-- Billing reminders log (new — Phase 4)
CREATE TABLE ci_billing_reminders_log (
    log_id          SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    reminder_day    SMALLINT NOT NULL,  -- 7,5,3,2,1
    sent_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    channel         VARCHAR(10)         -- email, sms, inapp
);

-- Landing page CMS content (new — Phase 5)
CREATE TABLE ci_landing_content (
    content_id      SERIAL PRIMARY KEY,
    section         VARCHAR(50) NOT NULL,  -- hero, features, faq, etc.
    content_key     VARCHAR(100) NOT NULL,
    content_value   TEXT,
    content_json    JSONB,                 -- for structured sections
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE (section, content_key)
);

-- PAYE tax bands (new — Phase 5)
CREATE TABLE ci_paye_bands (
    band_id         SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    min_income      NUMERIC(12,2) NOT NULL,
    max_income      NUMERIC(12,2),         -- NULL = no upper limit
    rate_percent    NUMERIC(5,2) NOT NULL,
    effective_from  DATE NOT NULL,
    is_active       SMALLINT DEFAULT 1
);
```

#### Backup strategy with PostgreSQL

```bash
# Automated daily backup script (add to cron on VPS)
#!/bin/bash
BACKUP_DIR=/var/backups/rooibok
DATE=$(date +%Y%m%d_%H%M%S)
docker exec rooibok_postgres pg_dump -U $DB_USER $DB_NAME \
  | gzip > $BACKUP_DIR/rooibok_$DATE.sql.gz

# Keep last 30 days only
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

# Upload to Backblaze B2 or AWS S3
# b2 upload-file rooibok-backups $BACKUP_DIR/rooibok_$DATE.sql.gz
```

---

### 0c — Rename to Rooibok HR System

**This is the first thing you do after the database is running.**

```sql
-- Run this once on the fresh PostgreSQL database
UPDATE ci_erp_settings
SET
    application_name = 'Rooibok HR System',
    company_name     = 'Rooibok HR System',
    trading_name     = 'Rooibok'
WHERE setting_id = 1;
```

**Files to update after logo replacement:**

```sql
UPDATE ci_erp_settings
SET
    logo          = 'rooibok-logo-white.png',
    favicon       = 'rooibok-favicon.png',
    frontend_logo = 'rooibok-logo-main.png'
WHERE setting_id = 1;
```

**Files to bulk find-replace "TimeHRM" → "Rooibok HR System":**

- `app/Views/frontend/components/htmlhead.php`
- `app/Views/frontend/components/top_link.php`
- `app/Views/frontend/components/footer.php`
- `app/Views/frontend/home.php`
- `app/Views/installation/components/` (all files)
- `app/Language/en/Frontend.php` (language strings)

**Logo files to replace in `public/uploads/` and `public/frontend/assets/`:**

- `hrsale-logo-white.png` → `rooibok-logo-white.png`
- `logo.png` → `rooibok-logo-main.png`
- `favicon_*.png` → `rooibok-favicon.png`
- `signin_logo_*.png` → `rooibok-signin-logo.png`

**Deliverable:** `docker compose up`, open browser, see "Rooibok HR System" everywhere.

---

## Phase 1 — Foundation

**Priority:** High — must complete before Phase 2  
**Estimated time:** 5–8 days  
**Goal:** Performance, caching, bug fixes. System becomes fast and stable.

---

### 1.1 — Redis Caching Layer

Install the PHP Redis extension (already in Dockerfile). Configure CI4 to use Redis for:

- Role permissions per user (currently re-queried on every page load)
- Company settings (currently re-queried on every page load)
- Shift lookup tables
- Holiday dates
- Dashboard KPI counts

```php
// app/Config/Cache.php
public string $handler = 'redis';
public string $backupHandler = 'file';

public array $redis = [
    'host'     => 'redis',         // Docker service name
    'password' => null,
    'port'     => 6379,
    'timeout'  => 0,
    'database' => 0,
];
```

**Usage pattern in controllers:**

```php
$cache = \Config\Services::cache();
$company_settings = $cache->get('company_settings_' . $company_id);
if ($company_settings === null) {
    $company_settings = $SystemModel->where('setting_id', 1)->first();
    $cache->save('company_settings_' . $company_id, $company_settings, 3600);
}
```

**Cache invalidation:** Clear relevant cache keys whenever settings are saved.

---

### 1.2 — Server-Side DataTables

The following tables load all rows into memory and return them at once. Each must be converted to server-side processing with database-level filtering, sorting, and pagination.

| Table / Module | Current behaviour | Fix |
|---------------|------------------|-----|
| Attendance list | Loads all employees + loops with sub-queries | Server-side, paginate 25 rows |
| Employee list | Loads all staff for company | Server-side, add DB index on company_id |
| Payroll list | All payroll records at once | Server-side |
| Leave list | All leave requests | Server-side |
| Invoice list | All invoices | Server-side |
| Visitor log | All visitors | Server-side |

**DataTables server-side pattern (PHP side):**

```php
public function employees_list_server() {
    $draw   = $this->request->getPost('draw');
    $start  = $this->request->getPost('start');
    $length = $this->request->getPost('length');
    $search = $this->request->getPost('search')['value'];

    $builder = $this->db->table('ci_users')
        ->where('company_id', $company_id)
        ->where('user_type', 'staff');

    if (!empty($search)) {
        $builder->groupStart()
            ->like('first_name', $search)
            ->orLike('last_name', $search)
            ->orLike('email', $search)
            ->groupEnd();
    }

    $total    = $builder->countAllResults(false);
    $filtered = $builder->countAllResults(false);
    $data     = $builder->limit($length, $start)->get()->getResultArray();

    return $this->response->setJSON([
        'draw'            => $draw,
        'recordsTotal'    => $total,
        'recordsFiltered' => $filtered,
        'data'            => $this->formatRows($data),
    ]);
}
```

---

### 1.3 — Bug Fixes

#### Bug 1 — total_rest silently lost in attendance loop

File: `app/Controllers/Erp/Timesheet.php`, method `attendance_list()`

The `$Trest` variable is set inside the loop then immediately overwritten to empty string before the array push. Fix: remove the erroneous reset lines and accumulate correctly.

#### Bug 2 — FILTER_SANITIZE_STRING deprecated in PHP 8.1+

Replace across all controllers:

```php
// Old (deprecated, throws notice in PHP 8.1+)
$value = $this->request->getPost('field', FILTER_SANITIZE_STRING);

// New
$value = strip_tags(trim($this->request->getPost('field')));
// Or use CI4's built-in:
$value = $this->request->getPost('field', true); // XSS clean
```

Run: `grep -rn "FILTER_SANITIZE_STRING" app/Controllers/` to find all ~80 occurrences.

#### Bug 3 — PayPal $sandbox undefined variable

File: `app/Config/Paypal.php`, constructor.

```php
// Old — $sandbox is undefined
$this->paypal_url = ($sandbox == FALSE) ? '...' : '...';

// This whole file is removed in Phase 3 (PayPal removal) — mark for deletion.
```

#### Bug 4 — Attendance status hardcoded English string

File: `app/Controllers/Erp/Timesheet.php`

```php
// Old — not translatable
'attendance_status' => 'Present',

// New
'attendance_status' => lang('Attendance.attendance_present'),
```

---

### 1.4 — Super Admin Settings UI — Complete Keys & Configuration Panel

**All operational keys and configuration live in the database and are editable from the Super Admin portal. The `.env` file holds infrastructure-only secrets that are never exposed in any UI.**

The existing settings page at `erp/system-settings` is extended with new tabs. Everything below is stored in `ci_erp_settings` and loaded at runtime via the `SystemModel`. No SSH access or file editing is needed for any of these after initial deployment.

#### Rule: what goes where

| Type | Where stored | Who can edit |
|------|-------------|-------------|
| DB password, DB name, DB user | `.env` only | Server admin via SSH — never in UI |
| All API keys (Stripe, MTN, Airtel, SMS, JWT) | `ci_erp_settings` DB column | Super Admin via settings UI |
| All feature toggles | `ci_erp_settings` DB column | Super Admin via settings UI |
| All branding (logo, name, colours) | `ci_erp_settings` DB column | Super Admin via settings UI |
| PAYE tax bands | `ci_paye_bands` table | Super Admin via Tax settings UI |
| Landing page content | `ci_landing_content` table | Super Admin via Landing Page UI |

#### New database columns to add to `ci_erp_settings`

```sql
-- Stripe
ALTER TABLE ci_erp_settings ADD COLUMN stripe_secret_key      VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN stripe_publishable_key  VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN stripe_webhook_secret   VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN stripe_mode             VARCHAR(10) DEFAULT 'test';
ALTER TABLE ci_erp_settings ADD COLUMN stripe_active           SMALLINT DEFAULT 0;

-- MTN Mobile Money
ALTER TABLE ci_erp_settings ADD COLUMN mtn_subscription_key   VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN mtn_api_user            VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN mtn_api_key             VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN mtn_environment         VARCHAR(20) DEFAULT 'sandbox';
ALTER TABLE ci_erp_settings ADD COLUMN mtn_active              SMALLINT DEFAULT 0;

-- Airtel Money
ALTER TABLE ci_erp_settings ADD COLUMN airtel_client_id        VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN airtel_client_secret    VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN airtel_environment      VARCHAR(20) DEFAULT 'sandbox';
ALTER TABLE ci_erp_settings ADD COLUMN airtel_active           SMALLINT DEFAULT 0;

-- SMS
ALTER TABLE ci_erp_settings ADD COLUMN sms_provider            VARCHAR(30) DEFAULT 'africastalking';
ALTER TABLE ci_erp_settings ADD COLUMN sms_username            VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN sms_api_key             VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN sms_sender_id           VARCHAR(15) DEFAULT 'RooibokHR';
ALTER TABLE ci_erp_settings ADD COLUMN sms_active              SMALLINT DEFAULT 0;

-- JWT / API
ALTER TABLE ci_erp_settings ADD COLUMN jwt_secret              VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN jwt_ttl_hours           SMALLINT DEFAULT 24;
ALTER TABLE ci_erp_settings ADD COLUMN api_active              SMALLINT DEFAULT 1;
ALTER TABLE ci_erp_settings ADD COLUMN api_rate_limit          SMALLINT DEFAULT 60;

-- Geofencing defaults (Super Admin sets system defaults; Company Admin can override per company)
ALTER TABLE ci_erp_settings ADD COLUMN default_geofence_radius INTEGER DEFAULT 300;

-- Billing reminders
ALTER TABLE ci_erp_settings ADD COLUMN billing_reminder_active SMALLINT DEFAULT 1;
ALTER TABLE ci_erp_settings ADD COLUMN billing_reminder_days   VARCHAR(20) DEFAULT '7,5,3,2,1';
```

#### Settings UI — tab structure (all under `erp/system-settings`)

**Tab: Stripe payments**
- Stripe secret key (masked password field)
- Stripe publishable key (masked)
- Stripe webhook secret (masked)
- Mode toggle: Test / Live — switches which key pair is used
- Enable/disable Stripe globally
- "Test Stripe connection" action button — calls `stripe.paymentMethods.list()` with 1 result to verify

**Tab: MTN Mobile Money**
- Subscription key (masked)
- API user ID
- API key (masked)
- Environment: Sandbox / Production dropdown
- Callback URL: auto-displayed as read-only `https://yourdomain.com/api/v1/webhooks/mtn`
- Enable/disable MTN globally

**Tab: Airtel Money**
- Client ID
- Client secret (masked)
- Environment: Sandbox / Production dropdown
- Callback URL: auto-displayed read-only
- Enable/disable Airtel globally

**Tab: SMS**
- Provider dropdown: Africa's Talking / Vonage / Twilio
- API username / SID
- API key / auth token (masked)
- Sender ID (max 11 chars, validated on input)
- Master SMS on/off toggle
- "Send test SMS" — sends to Super Admin phone number stored in profile

**Tab: Email / SMTP**
- SMTP host
- SMTP port (with SSL/TLS toggle)
- SMTP username
- SMTP password (masked)
- From name (e.g. "Rooibok HR System")
- "Send test email" action button

**Tab: API & Security**
- JWT secret — text field with "Generate new secret" button (shows confirmation warning that this logs out all API users)
- Token expiry in hours
- API master on/off toggle
- Rate limit (requests per minute)

**Tab: Tax (PAYE & NSSF)**
- NSSF employee rate %
- NSSF employer rate %
- NSSF enabled toggle
- PAYE bands table — add/edit/delete rows (min income, max income, rate %)
- Effective date per band set
- "These rates apply to all companies unless overridden at company level"

#### Loading keys at runtime — helper function

Replace any hardcoded `env()` calls for API keys with a cached DB lookup:

```php
// app/Helpers/main_helper.php — add this function
function system_setting(string $key): string {
    $cache = \Config\Services::cache();
    $settings = $cache->get('system_settings');
    if ($settings === null) {
        $SystemModel = new \App\Models\SystemModel();
        $settings = $SystemModel->where('setting_id', 1)->first();
        $cache->save('system_settings', $settings, 3600);
    }
    return $settings[$key] ?? '';
}

// Usage throughout controllers — replaces env() calls:
$stripe_key = system_setting('stripe_secret_key');
$mtn_key    = system_setting('mtn_subscription_key');
$sms_token  = system_setting('sms_api_key');
```

**Cache invalidation:** On every save of `ci_erp_settings`, clear the `system_settings` cache key so the next request loads fresh values.

#### Security: encrypting sensitive keys at rest

API keys stored in the database should be encrypted, not plain text. Use CI4's Encryption service:

```php
// Encrypt before saving to DB
$encrypter = \Config\Services::encrypter();
$encrypted = base64_encode($encrypter->encrypt($raw_key));
// Store $encrypted in ci_erp_settings

// Decrypt when loading
$decrypted = $encrypter->decrypt(base64_decode($encrypted_from_db));
```

The encryption key itself lives in `.env` as `encryption.key` — this is the one key that must stay in `.env` and never enter the database.

---

### 1.5 — Database Indexes

Add these indexes to the PostgreSQL schema for the most-queried patterns:

```sql
-- Most critical — these run on every page load
CREATE INDEX idx_users_company_type    ON ci_users(company_id, user_type);
CREATE INDEX idx_timesheet_employee    ON ci_timesheet(employee_id, attendance_date);
CREATE INDEX idx_timesheet_company     ON ci_timesheet(company_id, attendance_date);
CREATE INDEX idx_company_membership    ON ci_company_membership(company_id, expiry_date);
CREATE INDEX idx_leave_employee        ON ci_leave(employee_id, leave_status);
CREATE INDEX idx_payroll_company       ON ci_payroll(company_id, payroll_month);
CREATE INDEX idx_visitors_company      ON ci_visitors(company_id, visit_date);
CREATE INDEX idx_invoices_company      ON ci_invoices(company_id, created_at);
CREATE INDEX idx_transactions_company  ON ci_transactions(company_id, transaction_date);
```

---

## Phase 2 — Security and Auth

**Priority:** High  
**Estimated time:** 6–10 days  
**Goal:** Harden the system. Add 2FA, build the REST API that all hardware integrations depend on.

---

### 2.1 — Two-Factor Authentication (2FA)

**Library:** `pragmarx/google2fa` via Composer, or implement TOTP manually using the RFC 6238 algorithm.

**Implementation:**

1. Add columns to `ci_users`: `totp_secret VARCHAR(32)`, `totp_enabled SMALLINT DEFAULT 0`
2. On first 2FA setup: generate secret, display QR code for Google Authenticator / Authy
3. On login: after password check passes, if `totp_enabled = 1`, redirect to 2FA verification screen
4. Verify the 6-digit TOTP code. On pass: create session. On fail: log attempt, lock after 5 failures.
5. Provide backup codes (8 single-use codes) at setup time, stored hashed in `ci_totp_backup_codes`

**Who gets 2FA:**

- Super Admin: mandatory, cannot be disabled
- Company Admin: optional, configurable in settings
- Staff: not applicable

---

### 2.2 — REST API Layer with JWT Auth

Create a new route group at `/api/v1/` with JWT middleware instead of session middleware.

**New file:** `app/Controllers/Api/V1/` (new directory)

**Endpoints to build:**

```
POST   /api/v1/auth/token          — exchange company credentials for JWT
POST   /api/v1/attendance/clock-in  — scanner / kiosk clock-in
POST   /api/v1/attendance/clock-out — scanner / kiosk clock-out
GET    /api/v1/attendance/status    — current attendance status for employee
GET    /api/v1/employee/{id}        — employee lookup by ID or QR code value
POST   /api/v1/visitors/check-in    — visitor kiosk check-in
GET    /api/v1/subscription/status  — subscription status check
GET    /api/v1/health               — server health check (no auth)
```

**JWT configuration:**

```php
// app/Config/Jwt.php (new file)
class Jwt extends BaseConfig {
    public string $secret    = '';  // loaded from .env — minimum 32 chars
    public string $algorithm = 'HS256';
    public int    $ttl       = 86400;  // 24 hours
}
```

**Rate limiting:** Apply CI4's Throttle filter to all API routes — max 60 requests per minute per IP.

**Routes addition:**

```php
// app/Config/Routes.php — add at end
$routes->group('api/v1', ['filter' => 'throttle:60,1'], function ($routes) {
    $routes->post('auth/token',             'Api\V1\Auth::token');
    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        $routes->post('attendance/clock-in',  'Api\V1\Attendance::clockIn');
        $routes->post('attendance/clock-out', 'Api\V1\Attendance::clockOut');
        $routes->get('attendance/status',     'Api\V1\Attendance::status');
        $routes->get('employee/(:num)',        'Api\V1\Employees::show/$1');
        $routes->post('visitors/check-in',    'Api\V1\Visitors::checkIn');
        $routes->get('subscription/status',   'Api\V1\Subscription::status');
    });
    $routes->get('health', 'Api\V1\Health::index');
});
```

---

### 2.3 — Attendance Edit Audit Trail

Every manual edit or deletion of an attendance record must be logged.

```sql
-- Already created in Phase 0b — verify it exists
SELECT * FROM ci_attendance_audit LIMIT 1;
```

**In Timesheet controller, wrap every update/delete:**

```php
// Before any attendance update, log the old value
private function logAttendanceAudit(int $attendance_id, string $field, $old, $new): void {
    $this->db->table('ci_attendance_audit')->insert([
        'attendance_id' => $attendance_id,
        'company_id'    => $this->companyId,
        'changed_by'    => $this->userId,
        'field_changed' => $field,
        'old_value'     => (string) $old,
        'new_value'     => (string) $new,
        'changed_at'    => date('Y-m-d H:i:s'),
    ]);
}
```

Add "Audit Log" tab to the attendance update screen showing full history per record.

---

### 2.4 — Geofencing for Clock-In

Company Admin sets office location in Settings → Attendance:
- Office latitude and longitude
- Allowed radius in metres (e.g. 300)

On every `set_clocking()` call, validate submitted GPS coordinates:

```php
private function withinGeofence(float $lat, float $lng): bool {
    $settings   = erp_company_settings();
    $officeLat  = (float) $settings['office_latitude'];
    $officeLng  = (float) $settings['office_longitude'];
    $maxRadius  = (int)   $settings['geofence_radius_m'] ?: 500;

    // Haversine formula
    $earthRadius = 6371000; // metres
    $dLat = deg2rad($lat - $officeLat);
    $dLng = deg2rad($lng - $officeLng);
    $a    = sin($dLat/2)**2 + cos(deg2rad($officeLat)) * cos(deg2rad($lat)) * sin($dLng/2)**2;
    $dist = $earthRadius * 2 * asin(sqrt($a));

    return $dist <= $maxRadius;
}
```

**If outside geofence:**
- Clock-in is still recorded (do not silently fail)
- Set `geofence_flag = 1` on the attendance record
- Show warning badge on attendance list
- Company Admin can review flagged records

**New columns on `ci_timesheet`:**

```sql
ALTER TABLE ci_timesheet ADD COLUMN geofence_flag SMALLINT DEFAULT 0;
ALTER TABLE ci_timesheet ADD COLUMN clock_in_latitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN clock_in_longitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN clock_out_latitude_dec NUMERIC(10,7);
ALTER TABLE ci_timesheet ADD COLUMN clock_out_longitude_dec NUMERIC(10,7);
```

---

## Phase 3 — Payment Engine

**Priority:** High  
**Estimated time:** 10–15 days  
**Goal:** Remove PayPal. Implement Stripe auto-renew subscriptions. Add MTN MoMo and Airtel Money Uganda. Background job queue for all payment processing.

---

### 3.1 — Remove PayPal

**Files to delete:**

```
app/Config/Paypal.php
app/Controllers/Erp/Pay.php         (PayPal IPN handler)
```

**Database columns to remove from `ci_erp_settings`:**

```sql
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_email;
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_sandbox;
ALTER TABLE ci_erp_settings DROP COLUMN IF EXISTS paypal_active;
```

**Views to update:**

- `app/Views/erp/settings/settings.php` — remove PayPal settings section
- `app/Views/erp/membership/key_subscription.php` — remove PayPal payment button
- `app/Views/erp/membership/key_more_membership.php` — remove PayPal option
- `app/Views/frontend/pricing.php` — remove PayPal references

---

### 3.2 — Stripe Subscription Billing (Auto-Renew)

**Replace** `Stripe::Charge::create()` with Stripe Billing subscriptions API.

**New database columns on `ci_company_membership`:**

```sql
ALTER TABLE ci_company_membership ADD COLUMN billing_mode       VARCHAR(10) DEFAULT 'manual';
ALTER TABLE ci_company_membership ADD COLUMN stripe_customer_id VARCHAR(100);
ALTER TABLE ci_company_membership ADD COLUMN stripe_sub_id      VARCHAR(100);
ALTER TABLE ci_company_membership ADD COLUMN auto_renew         SMALLINT DEFAULT 0;
```

**Stripe flow:**

```
1. Client selects plan + "Pay by Card"
2. Stripe.js collects card in browser (card never touches your server)
3. On submit: create Stripe Customer + attach PaymentMethod
4. Create Stripe Subscription with price_id matching your plan
5. Stripe webhook: invoice.payment_succeeded → extend expiry, generate invoice
6. Stripe webhook: invoice.payment_failed  → retry logic, notify client
7. Client can cancel from Subscription → Billing Settings
```

**Webhook endpoint:** `POST /api/v1/webhooks/stripe` (no JWT auth — Stripe-signature verified instead)

```php
// Verify Stripe webhook signature
$payload = file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$secret  = env('STRIPE_WEBHOOK_SECRET');

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    return $this->response->setStatusCode(400);
}
```

**Super Admin UI configuration (Settings → Stripe tab):**

Enter the Stripe secret key, publishable key, and webhook secret directly in the settings UI. They are stored encrypted in `ci_erp_settings` and loaded at runtime via `system_setting()`. No `.env` entry needed.

---

### 3.3 — MTN Mobile Money Uganda

**API:** MTN MoMo Collections API  
**Docs:** `momodeveloper.mtn.com`  
**Sandbox:** Free registration, instant approval  
**Production:** Requires MTN Uganda business partnership agreement

**Flow:**

```
1. Client enters MTN phone number on payment page
2. Your server calls Collections /requesttopay endpoint
3. Client receives USSD prompt on their phone: "Pay UGX X,XXX to Rooibok HR?"
4. Client approves with their MTN MoMo PIN
5. MTN calls your callback URL with payment status
6. On SUCCESSFUL status: extend subscription, generate invoice, send confirmation
```

**New file:** `app/Libraries/MtnMomo.php`

```php
class MtnMomo {
    private string $baseUrl;
    private string $subscriptionKey;
    private string $apiUser;
    private string $apiKey;

    public function requestPayment(string $phone, int $amount, string $reference): array {
        $referenceId = $this->generateUuid();
        $response = $this->client->post('/collection/v1_0/requesttopay', [
            'headers' => [
                'Authorization'         => 'Bearer ' . $this->getToken(),
                'X-Reference-Id'        => $referenceId,
                'X-Target-Environment'  => env('MTN_ENVIRONMENT'), // sandbox or production
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            ],
            'json' => [
                'amount'     => $amount,
                'currency'   => 'UGX',
                'externalId' => $reference,
                'payer'      => ['partyIdType' => 'MSISDN', 'partyId' => $phone],
                'payerMessage' => 'Rooibok HR subscription payment',
                'payeeNote'    => 'Subscription renewal',
            ],
        ]);
        return ['reference_id' => $referenceId, 'status' => $response->getStatusCode()];
    }
}
```

**Super Admin UI configuration (Settings → MTN Mobile Money tab):**

Enter the subscription key, API user ID, API key, and environment in the settings UI. The callback URL (`https://rooibok.co.ug/api/v1/webhooks/mtn`) is auto-displayed as read-only for you to copy into your MTN developer dashboard. No `.env` entry needed.

---

### 3.4 — Airtel Money Uganda

**API:** Airtel Africa Payments API  
**Docs:** `developers.airtel.africa`  
**Pattern:** Same USSD push-to-pay as MTN

**New file:** `app/Libraries/AirtelMoney.php`

Same pattern as MTN but with Airtel-specific authentication (OAuth2 client credentials) and endpoint paths. Airtel uses `/merchant/v2/payments/` for collections.

**Super Admin UI configuration (Settings → Airtel Money tab):**

Enter the client ID, client secret, and environment in the settings UI. The callback URL is auto-displayed read-only. No `.env` entry needed.

**Important:** Both MTN and Airtel require the customer to actively approve the USSD prompt. These payment methods are always manual billing (Mode B). Auto-renew (Mode A) is card-only via Stripe.

---

### 3.5 — Billing Mode Selection

On the subscription/upgrade page, show the client two clear options:

```
[ Auto-renew ] — Card only. Charged automatically 3 days before expiry. No action needed.
[ Manual ]     — Pay with card, MTN MoMo, or Airtel Money. You choose when to renew.
```

**Business logic:**

- Selecting Auto-renew: creates Stripe Customer + Subscription. Sets `billing_mode = 'auto'`, `auto_renew = 1`
- Selecting Manual with card: one-time Stripe Charge (not subscription). Sets `billing_mode = 'manual'`
- Selecting MTN / Airtel: always manual. Sets `billing_mode = 'manual'`
- Client can switch from Auto → Manual at any time from Subscription settings (cancels Stripe subscription, keeps expiry date)
- Client cannot switch from Manual → Auto without entering card details again

---

### 3.6 — Background Job Queue (Beanstalkd)

**Container:** Already in compose.yml  
**PHP library:** `pda/pheanstalk` via Composer

**Jobs to move off the HTTP request cycle:**

| Job | Trigger | Processing time |
|-----|---------|----------------|
| Payroll run (all staff) | Click "Run Payroll" button | Could be 30–60s for large companies |
| Bulk payslip PDF generation | After payroll run | 1–5s per employee |
| Payslip email delivery | After payslip generation | Depends on SMTP |
| Payment confirmation email | After Stripe/MTN/Airtel callback | ~2s |
| Subscription invoice PDF | After any payment | ~1s |
| Billing reminder SMS batch | Daily cron trigger | Depends on staff count |
| DB backup | Nightly cron | Varies |

**New file:** `app/Libraries/Queue.php`

```php
class Queue {
    private \Pheanstalk\Pheanstalk $client;

    public function push(string $tube, array $payload, int $delay = 0): void {
        $job = \Pheanstalk\Job::fromRaw(json_encode($payload));
        $this->client->useTube($tube)
            ->withDelay($delay)
            ->put($job);
    }
}
```

**New CI4 Spark command:** `php spark queue:worker` — runs as a long-lived process in Docker, picks up jobs from all tubes and processes them.

**Usage in controllers:**

```php
// Instead of: $this->generatePayslipsPDF($payroll_id);  (blocks for 45 seconds)
// Do:
$queue->push('payroll', ['action' => 'generate_payslips', 'payroll_id' => $payroll_id]);
// Returns immediately — user sees "Processing..." message
```

---

## Phase 4 — Subscription Lifecycle

**Priority:** High  
**Estimated time:** 5–8 days  
**Goal:** Automated 7-day reminder system. Auto-disconnect on expiry. Seamless restore. Invoice generation on every payment.

---

### 4.1 — Daily Cron Job

**New CI4 Spark command:** `php spark billing:check`

**Add to server crontab (on VPS):**

```bash
# Edit with: crontab -e
# Run billing check every day at 07:00 Kampala time
0 7 * * * docker exec rooibok_app php spark billing:check >> /var/log/rooibok/billing.log 2>&1

# Run queue worker continuously (restart if it crashes)
@reboot docker exec -d rooibok_app php spark queue:worker
```

**What `billing:check` does, in order:**

1. Query all companies where `is_active = 1`
2. Calculate days remaining: `expiry_date - TODAY`
3. For each company:
   - **> 7 days remaining:** do nothing
   - **Exactly 7 days:** send Day 7 reminder (email + SMS + in-app)
   - **5 or 3 days:** send SMS reminder only
   - **2 days:** send email + SMS reminder
   - **1 day:** send urgent email + SMS + set `show_modal = 1` flag
   - **0 days (expired today):** set `is_active = 0`, send "expired" notification
4. Check `ci_billing_reminders_log` before sending — never send same reminder twice
5. Log every action to `/var/log/rooibok/billing.log`

---

### 4.2 — Reminder Channels

**In-app notification:**
- Store in `ci_notifications` table (or existing announcements system)
- Shown in notification bell AND as a persistent banner at top of all pages
- Banner colour: amber for 7–3 days, red for 2–1 days
- Banner includes "Renew Now" button linking to subscription page
- Banner disappears automatically on renewal

**Email reminders — subject line schedule:**

| Day | Subject |
|-----|---------|
| 7 | Your Rooibok HR subscription expires in 7 days |
| 5 | Reminder: Rooibok HR subscription expires in 5 days |
| 3 | Action needed: subscription expires in 3 days |
| 2 | Urgent: your Rooibok HR subscription expires tomorrow |
| 1 | Final notice: subscription expires TODAY |
| 0 | Your Rooibok HR subscription has expired |

**SMS format (keep under 160 chars):**

```
Rooibok HR: Your subscription expires in X days. Renew at rooibok.co.ug/erp to avoid interruption.
```

**SMS provider options for Uganda:**

| Provider | Notes | Recommended for Rooibok |
|----------|-------|------------------------|
| Africa's Talking | Kampala-based, UGX billing, excellent MTN + Airtel Uganda coverage, free sandbox | Yes — primary choice |
| Vonage (Nexmo) | International, reliable, UGX billing possible | Fallback |
| Twilio | International, widely used, USD billing | Fallback |

**All SMS credentials are configured in the Super Admin portal — not in `.env`.**

Go to: Super Admin → Settings → SMS tab. Set provider, API username, API key, and sender ID. Use the "Send test SMS" button to verify before going live. See section 1.4 for full field reference.

> The `.env` file does NOT contain SMS credentials. They are stored encrypted in `ci_erp_settings` and loaded at runtime via `system_setting()`. Changing provider or rotating an API key is done entirely from the browser.

---

### 4.3 — Auto-Disconnect on Expiry

`billing:check` sets `is_active = 0` when `expiry_date < NOW()`.

The existing `CheckLogin` filter already redirects to the expired page when `is_active = 0`. No change needed to the filter itself.

**What happens to staff users:** Their session is destroyed on next login. They see the same expired page. Only the Company Admin can renew.

**What is NOT deleted:** All employee data, attendance records, payroll history, invoices, documents — everything is preserved indefinitely. Data only lives as long as the company record exists. Expiry only locks access.

---

### 4.4 — Seamless Restore on Payment

When any payment is confirmed (Stripe webhook, MTN callback, Airtel callback, or Super Admin manual renewal):

```php
private function activateSubscription(int $companyId, int $membershipId, string $txRef): void {
    $membership = $this->MembershipModel->find($membershipId);
    $current    = $this->CompanymembershipModel
                       ->where('company_id', $companyId)
                       ->first();

    // If renewing early (before expiry): extend from current expiry date
    // If renewing after expiry: extend from today
    $baseDate = ($current['is_active'] == 1 && $current['expiry_date'] > date('Y-m-d'))
        ? $current['expiry_date']
        : date('Y-m-d');

    $daysToAdd  = $membership['plan_duration'] == 1 ? 30 : 365;
    $newExpiry  = date('Y-m-d', strtotime($baseDate . " +{$daysToAdd} days"));

    $this->CompanymembershipModel->where('company_id', $companyId)->set([
        'membership_id' => $membershipId,
        'expiry_date'   => $newExpiry,
        'is_active'     => 1,
        'updated_at'    => date('Y-m-d H:i:s'),
    ])->update();

    // Clear reminder log so the next cycle restarts cleanly
    $this->db->table('ci_billing_reminders_log')
             ->where('company_id', $companyId)
             ->delete();

    // Generate invoice
    $this->generateSubscriptionInvoice($companyId, $membershipId, $txRef);

    // Send confirmation
    $this->sendRenewalConfirmation($companyId, $newExpiry);
}
```

---

### 4.5 — Invoice Generation

**Library:** DOMPDF — `dompdf/dompdf` via Composer. Free, self-hosted, no API calls.

**Invoice template:** `app/Views/erp/invoices/subscription_invoice.php`

**Invoice number format:** `RBHR-2026-00001` (prefix + year + 5-digit sequence per company)

**Invoice fields:**
- Rooibok HR System logo and company details
- Invoice number and date
- Bill to: client company name and email
- Plan name, duration, and amount in UGX
- Payment method (Stripe / MTN MoMo / Airtel Money)
- Transaction reference number
- New expiry date
- "Thank you for your subscription" footer

**Storage:** `public/uploads/invoices/{company_id}/{invoice_number}.pdf`

**Delivery:**
1. PDF stored on server
2. Email sent with PDF attached
3. Client can download from Subscription → Invoice History at any time
4. Super Admin can view all invoices from Admin → Subscription Invoices

---

## Phase 5 — Landing Page, Demo and Compliance

**Priority:** Medium  
**Estimated time:** 8–12 days  
**Goal:** Super Admin can edit landing page content without touching code. Live demo account. Uganda PAYE/NSSF engine. Org chart.

---

### 5.1 — Landing Page CMS

**New Super Admin menu item:** Landing Page (only visible to `user_type = super_user`)

**New controller:** `app/Controllers/Erp/Landingpage.php`

**Editable sections (stored in `ci_landing_content`):**

| Section | What can be edited |
|---------|-------------------|
| `hero` | Headline text, subtitle, CTA button text, hero image upload |
| `features` | Up to 8 feature cards: title + description + icon name |
| `stats` | 3–4 stat figures: e.g. "500+ Companies", "99.9% Uptime" |
| `testimonials` | Up to 6 testimonials: name, company, quote, photo |
| `faq` | Up to 10 FAQ items: question + answer |
| `pricing` | Auto-pulled from `ci_membership` table — no CMS field needed |
| `contact` | Email, phone, WhatsApp number, physical address |
| `footer` | Copyright text, social media links |
| `seo` | Page title, meta description, Open Graph image |

**Pricing page:** Already pulls from `ci_membership` table dynamically. Updating plan names and prices in Super Admin → Membership Plans immediately updates the pricing page. No CMS config needed for this section.

**Demo link:** Add a "Try Live Demo" button to the nav and hero section. URL: `/demo` — handled by new controller action.

---

### 5.2 — Demo Account System

**New database flag:**

```sql
ALTER TABLE ci_users ADD COLUMN is_demo SMALLINT DEFAULT 0;
```

**Setup:**

1. Create one company account: `demo@rooibok.co.ug`, company name "Rooibok Demo Company", flag `is_demo = 1`
2. Pre-load with realistic Ugandan data:
   - 50 fake employees with Ugandan names
   - 90 days of attendance records
   - 3 months of payroll history
   - 20 leave requests (various statuses)
   - 10 invoices
   - 5 departments, 8 designations
3. Create a seed file: `docker/postgres/demo_seed.sql`

**Demo login route:** `GET /demo`

```php
public function demo(): RedirectResponse {
    $demoUser = $this->UsersModel->where('is_demo', 1)
                                  ->where('user_type', 'company')
                                  ->first();
    // Set a read-only session flag
    session()->set([
        'sup_username'   => ['sup_user_id' => $demoUser['user_id']],
        'is_demo_session' => true,
    ]);
    return redirect()->to(site_url('erp/desk'));
}
```

**Read-only enforcement:** Add a filter `DemoMode` that intercepts all POST requests during a demo session and returns a friendly "This is a demo — sign up for a free trial to save changes" flash message.

**Daily reset:** `php spark demo:reset` — Spark command that truncates demo company's mutable data and re-runs the seed. Scheduled at midnight:

```bash
0 0 * * * docker exec rooibok_app php spark demo:reset
```

---

### 5.3 — Uganda PAYE and NSSF Tax Engine

**New Admin section:** Super Admin → Tax Configuration (only visible to super_user, not company admins)

**PAYE bands table** (`ci_paye_bands`) already created in Phase 0b.

**Default URA PAYE bands to seed (verify against current URA rates before seeding):**

```sql
INSERT INTO ci_paye_bands (company_id, min_income, max_income, rate_percent, effective_from) VALUES
(0, 0,        235000,   0,   '2024-07-01'),  -- company_id=0 means system default
(0, 235001,   335000,   10,  '2024-07-01'),
(0, 335001,   410000,   20,  '2024-07-01'),
(0, 410001,   10000000, 30,  '2024-07-01'),  -- NULL max = no upper limit
(0, 10000001, NULL,     40,  '2024-07-01');
-- Note: verify exact current URA bands before going live
```

**NSSF configuration (add to `ci_erp_settings`):**

```sql
ALTER TABLE ci_erp_settings ADD COLUMN nssf_employee_rate NUMERIC(5,2) DEFAULT 5.00;
ALTER TABLE ci_erp_settings ADD COLUMN nssf_employer_rate NUMERIC(5,2) DEFAULT 10.00;
ALTER TABLE ci_erp_settings ADD COLUMN nssf_enabled SMALLINT DEFAULT 1;
```

**Payroll calculation function:**

```php
private function calculatePAYE(float $grossSalary): float {
    $bands = $this->db->table('ci_paye_bands')
        ->where('is_active', 1)
        ->orderBy('min_income', 'ASC')
        ->get()->getResultArray();

    $paye = 0;
    foreach ($bands as $band) {
        if ($grossSalary <= $band['min_income']) break;
        $upper    = $band['max_income'] ?? $grossSalary;
        $taxable  = min($grossSalary, $upper) - $band['min_income'];
        $paye    += $taxable * ($band['rate_percent'] / 100);
    }
    return round($paye, 2);
}
```

**Payslip will now show automatically:**
- Gross salary
- NSSF employee deduction (5% of gross)
- PAYE deduction (calculated from bands)
- Other deductions (loans, advance salary)
- Net pay

---

### 5.4 — Org Chart Module

**Library:** d3.js (already allowed on CDN) or generate static SVG server-side.

**Recommended approach:** Client-side render using d3.js tree layout.

- Source data: existing `ci_departments`, `ci_designations`, `ci_staff_details` tables
- Build JSON tree from PHP, pass to view
- d3.js renders interactive expandable/collapsable org chart
- Nodes show: profile photo thumbnail, name, designation, department
- Clicking a node opens the staff profile in a modal
- Export to PDF button (browser print with `@media print` styles)

**Route:** `erp/org-chart`  
**Permission:** New role resource `org_chart1` (view)

---

---

### 5.5 — Memo, Announcement and Bulk Broadcast System

**Purpose:** Allow Super Admin (to all company admins) and Company Admin (to their own employees and CRM clients) to compose and send personalised memos, announcements, and bulk messages via in-app notification, email, and SMS — all from a single composer inside the portal.

**Estimated time:** 5–7 days  
**Dependencies:** Phase 3.6 (Beanstalkd queue), Phase 1.4 (SMS + email settings already configured)

---

#### Who can send what to whom

| Sender | Audience options | Use case examples |
|--------|----------------|------------------|
| Super Admin | All company admins | System maintenance notice, new feature announcement, price change notice |
| Super Admin | Specific company admin(s) | Individual client notice, subscription warning |
| Company Admin | All their employees | Payslip ready, holiday announcement, office closure, policy update |
| Company Admin | Specific department(s) | Department-level memo |
| Company Admin | Specific employees (selected) | Individual notice, HR memo |
| Company Admin | Their CRM clients/customers | Service update, invoice reminder, promotional message |

---

#### Personalisation tokens

Every message body and subject line supports merge tokens that are replaced per recipient before sending:

| Token | Replaced with | Available for |
|-------|-------------|---------------|
| `{{first_name}}` | Recipient's first name | All |
| `{{last_name}}` | Recipient's last name | All |
| `{{full_name}}` | First + last name | All |
| `{{company_name}}` | Recipient's company name | Employees, clients |
| `{{date}}` | Today's date (formatted) | All |
| `{{month}}` | Current month name | All |
| `{{sender_name}}` | Name of the person sending | All |
| `{{department}}` | Employee's department | Employees only |
| `{{designation}}` | Employee's job title | Employees only |
| `{{plan_name}}` | Subscription plan name | Company admins only |
| `{{expiry_date}}` | Subscription expiry date | Company admins only |

**SMS token constraint:** SMS is limited to 160 characters. The composer shows a live character count as the message is typed and warns when the personalised version will exceed 160 chars (using the longest expected token replacement).

---

#### New database tables

```sql
-- Master broadcast record
CREATE TABLE ci_broadcasts (
    broadcast_id    SERIAL PRIMARY KEY,
    company_id      INTEGER,           -- NULL = Super Admin broadcast to all companies
    created_by      INTEGER NOT NULL,  -- user_id of sender
    broadcast_type  VARCHAR(20) NOT NULL, -- memo, announcement, payslip_notice, custom
    subject         TEXT NOT NULL,
    body_html       TEXT,              -- rich HTML email body with tokens
    body_sms        VARCHAR(320),      -- plain text SMS body with tokens (max 2 SMS)
    audience_type   VARCHAR(30) NOT NULL, -- all_employees, department, individual, crm_clients, all_company_admins
    audience_ids    JSONB,             -- array of dept/user IDs if targeted; NULL = all
    channels        JSONB NOT NULL,    -- {"inapp": true, "email": true, "sms": false}
    status          VARCHAR(20) DEFAULT 'draft', -- draft, queued, sending, sent, failed
    scheduled_at    TIMESTAMP WITH TIME ZONE,    -- NULL = send immediately
    sent_at         TIMESTAMP WITH TIME ZONE,
    total_recipients INTEGER DEFAULT 0,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- One row per recipient per broadcast
CREATE TABLE ci_broadcast_log (
    log_id          SERIAL PRIMARY KEY,
    broadcast_id    INTEGER NOT NULL REFERENCES ci_broadcasts(broadcast_id),
    recipient_id    INTEGER NOT NULL,       -- user_id or CRM client id
    recipient_type  VARCHAR(20) NOT NULL,   -- employee, company_admin, crm_client
    recipient_email VARCHAR(200),
    recipient_phone VARCHAR(30),
    personalised_subject TEXT,             -- final subject with tokens replaced
    personalised_body    TEXT,             -- final body with tokens replaced
    personalised_sms     VARCHAR(320),
    inapp_sent      SMALLINT DEFAULT 0,
    email_sent      SMALLINT DEFAULT 0,
    email_opened    SMALLINT DEFAULT 0,    -- tracked via pixel
    sms_sent        SMALLINT DEFAULT 0,
    sms_status      VARCHAR(30),           -- provider status: delivered, failed, etc.
    error_message   TEXT,
    queued_at       TIMESTAMP WITH TIME ZONE,
    sent_at         TIMESTAMP WITH TIME ZONE
);

-- Templates saved for reuse
CREATE TABLE ci_broadcast_templates (
    template_id     SERIAL PRIMARY KEY,
    company_id      INTEGER,               -- NULL = system-wide template (Super Admin)
    template_name   VARCHAR(100) NOT NULL,
    subject         TEXT,
    body_html       TEXT,
    body_sms        VARCHAR(320),
    category        VARCHAR(50),           -- payroll, hr, general, marketing
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

---

#### Controller and routes

**New file:** `app/Controllers/Erp/Broadcasts.php`

```php
namespace App\Controllers\Erp;

class Broadcasts extends BaseController {

    // List all broadcasts sent by this company/super admin
    public function index(): string { }

    // Composer view — new broadcast
    public function create(): string { }

    // Save draft
    public function save_draft(): ResponseInterface { }

    // Preview — shows personalised output for one sample recipient
    public function preview(): ResponseInterface { }

    // Send now or schedule
    public function send(): ResponseInterface { }

    // Broadcast details + per-recipient delivery log
    public function details(): string { }

    // Template management
    public function templates(): string { }
    public function save_template(): ResponseInterface { }

    // AJAX: live recipient count as audience selection changes
    public function recipient_count(): ResponseInterface { }
}
```

**Routes to add in `app/Config/Routes.php`:**

```php
$routes->group('erp', ['filter' => 'companyauth'], function($routes) {
    $routes->get( 'broadcasts',                 'Erp\Broadcasts::index');
    $routes->get( 'broadcasts/create',          'Erp\Broadcasts::create');
    $routes->post('broadcasts/save-draft',      'Erp\Broadcasts::save_draft');
    $routes->post('broadcasts/preview',         'Erp\Broadcasts::preview');
    $routes->post('broadcasts/send',            'Erp\Broadcasts::send');
    $routes->get( 'broadcasts/details/(:num)',  'Erp\Broadcasts::details/$1');
    $routes->get( 'broadcasts/templates',       'Erp\Broadcasts::templates');
    $routes->post('broadcasts/save-template',   'Erp\Broadcasts::save_template');
    $routes->get( 'broadcasts/recipient-count', 'Erp\Broadcasts::recipient_count');
});
```

---

#### The personalisation engine — core function

```php
// app/Libraries/BroadcastPersonaliser.php

class BroadcastPersonaliser {

    /**
     * Replace all {{tokens}} in a string with recipient-specific values.
     * Called once per recipient per broadcast.
     */
    public function personalise(string $template, array $recipient, array $sender): string {
        $tokens = [
            '{{first_name}}'    => $recipient['first_name'] ?? '',
            '{{last_name}}'     => $recipient['last_name']  ?? '',
            '{{full_name}}'     => trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? '')),
            '{{company_name}}'  => $recipient['company_name'] ?? '',
            '{{department}}'    => $recipient['department_name'] ?? '',
            '{{designation}}'   => $recipient['designation_name'] ?? '',
            '{{plan_name}}'     => $recipient['plan_name'] ?? '',
            '{{expiry_date}}'   => isset($recipient['expiry_date'])
                                    ? date('d M Y', strtotime($recipient['expiry_date'])) : '',
            '{{date}}'          => date('d M Y'),
            '{{month}}'         => date('F Y'),
            '{{sender_name}}'   => trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? '')),
        ];
        return str_replace(array_keys($tokens), array_values($tokens), $template);
    }

    /**
     * Build the recipient list from audience_type + audience_ids.
     * Returns array of user rows with all needed fields for personalisation.
     */
    public function buildRecipientList(array $broadcast, int $companyId): array {
        $UsersModel      = new \App\Models\UsersModel();
        $DepartmentModel = new \App\Models\DepartmentModel();

        return match($broadcast['audience_type']) {
            'all_employees'       => $UsersModel->where('company_id', $companyId)
                                                ->where('user_type', 'staff')
                                                ->where('is_active', 1)->findAll(),
            'department'          => $this->getByDepartments($broadcast['audience_ids'], $companyId),
            'individual'          => $this->getByIds($broadcast['audience_ids']),
            'crm_clients'         => $this->getCrmClients($broadcast['audience_ids'], $companyId),
            'all_company_admins'  => $UsersModel->where('user_type', 'company')->findAll(),
            default               => [],
        };
    }
}
```

---

#### Send flow — step by step

```php
// In Broadcasts::send() controller method

public function send(): ResponseInterface {
    $broadcastId = $this->request->getPost('broadcast_id');
    $broadcast   = $this->BroadcastModel->find($broadcastId);
    $sender      = $this->UsersModel->find($this->userId);

    // 1. Build recipient list
    $personaliser = new BroadcastPersonaliser();
    $recipients   = $personaliser->buildRecipientList($broadcast, $this->companyId);

    // 2. Update total_recipients count
    $this->BroadcastModel->update($broadcastId, [
        'total_recipients' => count($recipients),
        'status'           => 'queued',
    ]);

    // 3. Queue one job per recipient — never a single giant bulk job
    $queue = new \App\Libraries\Queue();
    foreach ($recipients as $recipient) {
        $subject = $personaliser->personalise($broadcast['subject'],   $recipient, $sender);
        $body    = $personaliser->personalise($broadcast['body_html'], $recipient, $sender);
        $sms     = $personaliser->personalise($broadcast['body_sms'],  $recipient, $sender);

        // Write log row first (status: queued)
        $logId = $this->BroadcastLogModel->insert([
            'broadcast_id'         => $broadcastId,
            'recipient_id'         => $recipient['user_id'],
            'recipient_type'       => $recipient['user_type'],
            'recipient_email'      => $recipient['email'],
            'recipient_phone'      => $recipient['phone'] ?? '',
            'personalised_subject' => $subject,
            'personalised_body'    => $body,
            'personalised_sms'     => $sms,
            'queued_at'            => date('Y-m-d H:i:s'),
        ]);

        // Push to Beanstalkd — worker handles actual delivery
        $queue->push('broadcasts', [
            'log_id'      => $logId,
            'channels'    => json_decode($broadcast['channels'], true),
        ]);
    }

    return $this->response->setJSON(['success' => true, 'queued' => count($recipients)]);
}
```

---

#### Background worker — delivery

The queue worker (already built in Phase 3.6) handles the `broadcasts` tube:

```php
// In queue worker — broadcasts tube handler
case 'broadcasts':
    $log      = $BroadcastLogModel->find($job['log_id']);
    $channels = $job['channels'];

    // In-app notification
    if ($channels['inapp']) {
        $NotificationModel->insert([
            'user_id'     => $log['recipient_id'],
            'company_id'  => $log['company_id'],
            'title'       => $log['personalised_subject'],
            'body'        => strip_tags($log['personalised_body']),
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $BroadcastLogModel->update($log['log_id'], ['inapp_sent' => 1]);
    }

    // Email
    if ($channels['email'] && $log['recipient_email']) {
        $emailResult = send_html_email(
            $log['recipient_email'],
            $log['personalised_subject'],
            $log['personalised_body']
        );
        $BroadcastLogModel->update($log['log_id'], ['email_sent' => $emailResult ? 1 : 0]);
    }

    // SMS
    if ($channels['sms'] && $log['recipient_phone'] && !empty($log['personalised_sms'])) {
        $smsResult = send_sms($log['recipient_phone'], $log['personalised_sms']);
        $BroadcastLogModel->update($log['log_id'], [
            'sms_sent'   => $smsResult['success'] ? 1 : 0,
            'sms_status' => $smsResult['status'],
        ]);
    }
    break;
```

---

#### UI composer spec

**Route:** `erp/broadcasts/create`  
**Access:** Company Admin and Super Admin only  
**Layout:** Full-width wizard with 4 steps

**Step 1 — Audience**
- Audience type selector: All employees / By department / Specific employees / CRM clients (only shows if CRM module enabled) / All company admins (Super Admin only)
- If department: multi-select department checkboxes
- If individual: searchable employee lookup with checkboxes
- Live counter: "X recipients will receive this message"

**Step 2 — Message**
- Broadcast type tag: Memo / Announcement / Payroll notice / HR notice / General / Custom
- Subject line — text field with token insert buttons: `{{first_name}}` `{{company_name}}` `{{date}}`
- Body — rich text editor (Quill.js or TinyMCE — already used in the system for email templates) with same token insert buttons
- SMS body — separate plain text field, 160-char counter with warning, same token buttons
- Channel toggles: In-app (always on), Email, SMS
- "Use template" button — loads from `ci_broadcast_templates`
- "Save as template" button

**Step 3 — Preview**
- Shows the personalised output for one sample recipient (randomly picked from the audience list)
- Toggle to preview Email view / SMS view / In-app notification view
- Shows exactly what "John Doe from Sales department" will receive

**Step 4 — Schedule**
- Send now — immediately queues all jobs
- Schedule — datetime picker (date + time), timezone shown (Africa/Kampala)
- Summary: "X recipients · Email + SMS · Sending now / on [date]"
- Confirm & Send button

---

#### Delivery report view

**Route:** `erp/broadcasts/details/{id}`

Shows a dashboard for each broadcast:

```
Total recipients: 87        Sent: 85        Failed: 2

In-app:  87/87 delivered
Email:   83/85 sent  (3 opened — open tracking)
SMS:     78/85 sent  (7 failed — wrong numbers)

[Export CSV]  [Retry failed]  [View recipient log]
```

The per-recipient log table shows: name, email, channels sent, status, timestamps.

**Retry failed:** Queues a new job only for rows where `email_sent = 0` or `sms_sent = 0`, skipping successful deliveries.

---

#### Scheduled broadcasts — cron

```bash
# Check every 5 minutes for broadcasts due to send
*/5 * * * * docker exec rooibok_app php spark broadcasts:dispatch
```

`php spark broadcasts:dispatch` queries `ci_broadcasts` for records where:
- `status = 'queued'`
- `scheduled_at <= NOW()`

Picks them up and queues the per-recipient jobs.

---

#### Role permissions for broadcasts

```
broadcast1  — View broadcast list and reports
broadcast2  — Create and send broadcasts
broadcast3  — Manage templates
broadcast4  — Delete broadcasts
```

Add to `ci_roles` and `ci_staff_roles` tables. Company Admin has all four by default. Super Admin always has full access. Staff have no access to this module — they only receive broadcasts.

---

#### Audit — items to add to Phase 8 audit checklist

- [ ] Broadcast composer accessible to Company Admin at `erp/broadcasts/create`
- [ ] Super Admin sees "All company admins" as an audience option
- [ ] Company Admin does NOT see "All company admins" as option
- [ ] `{{first_name}}` token resolves correctly in subject, email body, and SMS
- [ ] Token `{{company_name}}` resolves to recipient's company — not sender's company
- [ ] SMS with `{{first_name}}` replaced stays under 160 chars for all test names
- [ ] Preview step shows accurate personalised output
- [ ] "Send now" queues one Beanstalkd job per recipient — not one bulk job
- [ ] Worker delivers in-app, email, and SMS correctly for all three channels
- [ ] Delivery report shows correct sent/failed counts
- [ ] Retry failed re-queues only failed recipients, not all
- [ ] Scheduled broadcast fires within 5 minutes of scheduled time
- [ ] Staff cannot access `erp/broadcasts` URL — redirected away
- [ ] CRM client SMS works only if recipient has phone number on record

---

#### Tests to add to Phase 9 test matrix

| Test | Expected result |
|------|----------------|
| Compose broadcast to all employees, send now | All active staff receive in-app notification within 60 seconds |
| Use `{{first_name}}` in subject | Each recipient receives their own first name in subject line |
| Select "By department" — Sales only | Only Sales department employees receive the message |
| Send email + SMS to one employee with no phone number | Email sent, SMS skipped silently, log shows sms_sent = 0 |
| Schedule broadcast for 5 minutes from now | Broadcast fires within 5–10 minutes |
| Super Admin sends to all company admins | All company admin users receive notification |
| Company Admin attempts to send to another company's employees | 403 — company isolation enforced |
| Retry failed button | Only failed recipients requeued — successful ones not duplicated |
| Save template and reload in new composer | Template pre-fills subject and body correctly |
| Send SMS with body > 160 chars after personalisation | Warning shown in composer — SMS body truncated or blocked |

---

## Phase 6 — Attendance Hardware

**Priority:** Medium  
**Estimated time:** 6–10 days  
**Goal:** QR scanner, RFID/biometric middleware, visitor kiosk, live attendance board.

---

### 6.1 — QR Code Attendance

**Employee QR code generation:**

```php
// Each employee gets a unique QR code containing their encoded employee_id
// Library: endroid/qr-code via Composer
$qrCode = QrCode::create(uencode($employee_id))
    ->setSize(200)
    ->setMargin(10);
$writer = new PngWriter();
$result = $writer->write($qrCode);
```

**QR codes accessible from:**
- Staff profile page — downloadable PNG
- Printable employee ID card template (new A6 print view)

**Scanning setup:**

- Any Android tablet (UGX 200,000–400,000) at the office gate
- Chrome browser in kiosk mode, pointing to `https://rooibok.co.ug/kiosk`
- Use tablet camera or USB barcode scanner (handheld, UGX 80,000–150,000)

**Kiosk page** (`/kiosk`):

- Full-screen, minimal interface
- Camera live preview
- On QR scan: calls REST API `POST /api/v1/attendance/clock-in`
- Shows employee photo + name + "Clocked In at 08:32" for 3 seconds
- Returns to camera view automatically

---

### 6.2 — ZKTeco / RFID Biometric Middleware

**Hardware options (available in Kampala):**

| Device | Type | Approx. Price UGX |
|--------|------|------------------|
| ZKTeco K40 | Fingerprint + RFID | 450,000 |
| ZKTeco F18 | Fingerprint + RFID | 550,000 |
| ZKTeco MB10 | Face recognition | 1,200,000 |
| Generic 125kHz RFID reader | Card only | 80,000 |

**ZKTeco integration approach:**

ZKTeco devices can push attendance logs to a remote server via HTTP. Configure the device's "Push Protocol" to point to your server:

```
Server address: rooibok.co.ug
Port: 443
Path: /api/v1/webhooks/zkteco
```

**New webhook endpoint:**

```php
// app/Controllers/Api/V1/Webhooks.php
public function zkteco(): ResponseInterface {
    // ZKTeco sends XML or JSON depending on firmware version
    $payload = $this->request->getBody();
    // Parse employee ID, punch time, device ID
    // Call same clock-in logic as REST API
    // Return 200 OK
}
```

**Alternative — ZKLIB PHP:** Use `zikeproject/zklib` Composer package to actively pull attendance logs from the device on a schedule, rather than waiting for the device to push.

---

### 6.3 — Visitor Kiosk Mode

**New route:** `GET /visitor-kiosk` — no login required, pre-authenticated kiosk session

**New view:** `app/Views/erp/visitors/kiosk.php`

**Interface:**
- Large touch-friendly form fields (designed for 10" tablet)
- Fields: Visitor name, Phone number, Whom are you visiting (staff dropdown), Purpose (dropdown), National ID number (optional)
- Optional: Camera capture for visitor photo using `<input type="file" accept="image/*" capture="camera">`
- On submit: creates visitor record, prints or shows a visitor badge QR code
- Badge QR code encodes visitor_id — scanning it at exit calls check-out API endpoint

**Auto check-out reminder:**

```bash
# Every hour, check for visitors who checked in > 8 hours ago with no check-out
0 * * * * docker exec rooibok_app php spark visitors:remind-checkout
```

---

### 6.4 — Live Attendance Dashboard

**Technology:** Server-Sent Events (SSE) — no WebSocket server needed.

**New SSE endpoint:**

```php
// app/Controllers/Erp/Attendance.php
public function liveStream(): ResponseInterface {
    $this->response->setHeader('Content-Type', 'text/event-stream');
    $this->response->setHeader('Cache-Control', 'no-cache');
    $this->response->setHeader('X-Accel-Buffering', 'no');  // disable nginx buffering

    while (true) {
        $data = $this->getAttendanceSummary();
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush(); flush();
        sleep(30);  // push update every 30 seconds
    }
}
```

**Client-side:**

```javascript
const source = new EventSource('/erp/attendance-live');
source.onmessage = function(e) {
    const data = JSON.parse(e.data);
    updateAttendanceBoard(data);
};
```

**TV display mode:** Add `?display=tv` parameter that renders a fullscreen attendance board with no navigation, suitable for an office monitor showing who is currently in the building.

---

## Phase 7 — Printing and Final Polish

**Priority:** Medium-Low  
**Estimated time:** 6–10 days  
**Goal:** Invoice printing (A4 and thermal receipt). Expense claims module. Mobile app groundwork.

---

### 7.1 — Browser Print for A4 Invoices

DOMPDF is already generating PDFs from Phase 4. Add a print button to every invoice view.

**Print CSS** (`public/assets/css/print.css`):

```css
@media print {
    .sidebar, .navbar, .breadcrumb, .btn, footer { display: none !important; }
    .invoice-container { width: 100%; margin: 0; padding: 0; }
    body { font-size: 12pt; }
    @page { size: A4; margin: 15mm; }
}
```

**Print button in invoice view:**

```html
<button onclick="window.print()" class="btn btn-primary">
    <i class="feather icon-printer"></i> Print Invoice
</button>
```

This works with any printer visible to the browser — USB, WiFi, or network printer. Zero additional configuration.

---

### 7.2 — Thermal Receipt Printer (Front Desk)

**Recommended hardware:**

| Model | Price UGX (approx.) | Connection |
|-------|--------------------|-----------| 
| Epson TM-T20III | 420,000 | USB / Ethernet |
| Xprinter XP-58 | 180,000 | USB |
| RONGTA RP58 | 150,000 | USB / Bluetooth |

**Architecture:**

```
Browser (reception PC) → localhost:6500 (Node.js print service) → USB/Ethernet printer
```

**Node.js print service** (runs on the reception computer, not the server):

Install once on the reception PC:

```bash
npm install -g node-thermal-printer express
```

`print-service.js`:

```javascript
const express = require('express');
const { ThermalPrinter, PrinterTypes } = require('node-thermal-printer');
const app = express();
app.use(express.json());

app.post('/print-receipt', async (req, res) => {
    const { company, plan, amount, date, invoice_no, expiry } = req.body;
    const printer = new ThermalPrinter({
        type: PrinterTypes.EPSON,
        interface: 'usb'  // or 'tcp://192.168.1.100:9100' for network printer
    });

    printer.alignCenter();
    printer.bold(true);
    printer.println('ROOIBOK HR SYSTEM');
    printer.bold(false);
    printer.println('Subscription Receipt');
    printer.drawLine();
    printer.alignLeft();
    printer.println(`Invoice: ${invoice_no}`);
    printer.println(`Company: ${company}`);
    printer.println(`Plan:    ${plan}`);
    printer.println(`Amount:  UGX ${Number(amount).toLocaleString()}`);
    printer.println(`Date:    ${date}`);
    printer.println(`Expires: ${expiry}`);
    printer.drawLine();
    printer.alignCenter();
    printer.println('Thank you!');
    printer.println('rooibok.co.ug');
    printer.cut();

    await printer.execute();
    res.json({ success: true });
});

app.listen(6500, () => console.log('Print service running on port 6500'));
```

**In the web app — "Print Receipt" button:**

```javascript
async function printThermalReceipt(invoiceData) {
    try {
        const response = await fetch('http://localhost:6500/print-receipt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(invoiceData)
        });
        if (response.ok) showSuccess('Receipt printed successfully');
    } catch (e) {
        // Fallback: open PDF in browser tab for manual print
        window.open('/erp/invoice-pdf/' + invoiceData.invoice_id, '_blank');
    }
}
```

The `try/catch` ensures that if the print service is not running (e.g. nobody is at the reception desk), the system gracefully falls back to the PDF browser print.

---

### 7.3 — Expense Claims Module

**New tables:**

```sql
CREATE TABLE ci_expenses (
    expense_id      SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    employee_id     INTEGER NOT NULL,
    category_id     INTEGER,
    amount          NUMERIC(12,2) NOT NULL,
    currency        VARCHAR(10) DEFAULT 'UGX',
    description     TEXT,
    expense_date    DATE NOT NULL,
    receipt_path    VARCHAR(300),
    status          VARCHAR(20) DEFAULT 'pending',
    approved_by     INTEGER,
    approved_at     TIMESTAMP WITH TIME ZONE,
    payroll_month   VARCHAR(7),              -- added to payroll when approved
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE ci_expense_categories (
    category_id     SERIAL PRIMARY KEY,
    company_id      INTEGER NOT NULL,
    category_name   VARCHAR(100) NOT NULL,
    is_active       SMALLINT DEFAULT 1
);
```

**Workflow:**

1. Staff submits expense from their profile: amount, category, date, description, receipt photo upload
2. Manager/Company Admin sees pending expenses in a new Expenses module
3. Approved expense: moves to Payroll → next payroll run includes it as a reimbursement line
4. Rejected expense: staff notified via in-app notification
5. Report: Expenses by employee, by category, by month

**Permissions:** New role resources `expense1` (view), `expense2` (add), `expense3` (approve), `expense4` (delete)

---

### 7.4 — Mobile App Groundwork

The REST API built in Phase 2 is the entire foundation for a mobile app. No additional backend work is needed in Phase 7.

**Document the API** — generate OpenAPI 3.0 spec:

```bash
# Install swagger-php
composer require zircote/swagger-php --dev

# Generate spec
./vendor/bin/openapi app/Controllers/Api --output public/api-docs/openapi.json
```

Expose at `GET /api/docs` — interactive Swagger UI for any developer building the mobile app.

**Recommended mobile stack when ready:** Flutter (single codebase for Android + iOS). Key screens for Phase 8 (mobile app):
- Clock in / clock out with GPS
- View payslip and download PDF
- Submit leave request
- Check attendance history
- View announcements

---

## Phase 8 — Full Post-Execution Audit

**Priority:** Mandatory before production launch  
**Estimated time:** 3–5 days  
**Goal:** Verify that every item in Phases 0–7 was executed correctly, securely, and completely. This is a structured technical audit — not testing (that is Phase 9).

---

### 8.1 — Architecture Audit

Verify each of the following by direct inspection:

- [ ] Docker containers start cleanly with `docker compose up -d` — no errors in logs
- [ ] PostgreSQL 16 is running — verify with `docker exec rooibok_postgres psql -c "SELECT version();"`
- [ ] PHP version is 8.2 — verify with `docker exec rooibok_app php -v`
- [ ] Redis is running and accepting connections — verify with `docker exec rooibok_redis redis-cli ping`
- [ ] Beanstalkd is running — verify with `docker exec rooibok_queue beanstalkd -v`
- [ ] All Docker volumes are persisting data (restart containers, verify data survives)
- [ ] Nginx is serving HTTPS with valid SSL certificate
- [ ] `certbot renew --dry-run` completes without error
- [ ] `.env.prod` is NOT tracked in git — verify with `git status`
- [ ] Production `.env.prod` uses live API keys, not sandbox keys

---

### 8.2 — Database Audit

- [ ] All ~95 original MySQL tables have been converted and exist in PostgreSQL
- [ ] All 9 new tables from the upgrade plan exist and have correct columns
- [ ] All database indexes are present — run `\d tablename` in psql to verify
- [ ] No MySQL-specific syntax remains — grep for `AUTO_INCREMENT`, `ENGINE=`, `CHARSET=`
- [ ] No `DATE_FORMAT()` calls remain in PHP code — grep to confirm
- [ ] No `IFNULL()` calls remain — grep to confirm
- [ ] No `GROUP_CONCAT()` calls remain — grep to confirm
- [ ] Foreign key constraints are defined on all relationship columns
- [ ] `ci_erp_settings.application_name` = 'Rooibok HR System'
- [ ] Automated daily backup cron is running — check crontab and verify a backup file was created

---

### 8.3 — Branding Audit

- [ ] "TimeHRM" does not appear anywhere visible to users — search all view files
- [ ] "HRSALE" does not appear anywhere visible to users
- [ ] `timehrm.com` does not appear in any user-facing content
- [ ] Rooibok logo appears on: login page, dashboard header, public landing page, invoices, email templates
- [ ] Favicon is updated
- [ ] Email sender name shows "Rooibok HR System", not "TimeHRM"

---

### 8.4 — Security Audit

- [ ] 2FA is enforced for the Super Admin account — test that login without TOTP is rejected
- [ ] JWT tokens expire after 24 hours — verify by decoding a token at jwt.io
- [ ] Stripe webhook signature verification is working — send a test event from Stripe dashboard
- [ ] MTN MoMo callback validates the expected fields before processing
- [ ] Airtel callback validates authenticity before processing
- [ ] Rate limiting is active on API routes — verify 60 req/min limit is enforced
- [ ] All user inputs are sanitised — `strip_tags()` or CI4 XSS clean on every POST field
- [ ] `FILTER_SANITIZE_STRING` is gone from all controllers — grep to confirm
- [ ] SQL injection protection: all queries use CI4 query builder or prepared statements — audit any raw SQL
- [ ] pgAdmin is not publicly accessible in production — verify nginx blocks port 5050 from outside
- [ ] Mailhog is not running in production — verify `--profile dev` containers are not running
- [ ] SSL/TLS is A-grade — test at `ssllabs.com/ssltest/`
- [ ] `CI_ENVIRONMENT = production` in `.env.prod` — verify error details are not shown to users

---

### 8.5 — Payment Audit

- [ ] PayPal config file is deleted — verify `app/Config/Paypal.php` does not exist
- [ ] PayPal route is removed — verify `/erp/paypal*` routes return 404
- [ ] Stripe one-time charge code is replaced by Stripe Subscription code
- [ ] Stripe test mode: make a successful subscription payment and verify: subscription extended, invoice generated, confirmation email sent, PDF downloadable
- [ ] Stripe test mode: simulate a failed payment and verify: retry logic triggers, client is notified, subscription is not extended
- [ ] MTN sandbox: initiate a payment request and verify USSD prompt is triggered
- [ ] Airtel sandbox: initiate a payment request and verify USSD prompt is triggered
- [ ] Billing mode switching (Auto → Manual and back) works without data loss
- [ ] Invoice numbers are sequential and unique

---

### 8.6 — Subscription Lifecycle Audit

- [ ] `php spark billing:check` runs without errors
- [ ] Day 7 reminder: manually set a company's expiry to 7 days from now, run billing check, verify email + SMS + in-app notification all fired
- [ ] Day 1 reminder: manually set expiry to tomorrow, run billing check, verify urgent notifications
- [ ] Auto-disconnect: manually set expiry to yesterday, run billing check, verify `is_active = 0` and all logins for that company redirect to expired page
- [ ] Restore after expiry: make a payment for the expired company, verify `is_active = 1` and all staff can log in again immediately
- [ ] Early renewal: renew while still active, verify new expiry = old expiry + plan days (not today + plan days)
- [ ] `ci_billing_reminders_log` is preventing duplicate reminders — verify same reminder is not sent twice on consecutive cron runs

---

### 8.7 — Landing Page and Demo Audit

- [ ] Landing page CMS is accessible to Super Admin at `erp/landing-page`
- [ ] Changing hero headline in CMS updates `rooibok.co.ug` immediately
- [ ] Pricing page reflects current membership plans from database
- [ ] "Try Demo" button on landing page logs user into demo session
- [ ] Demo session is read-only — POST requests return friendly message, no data is saved
- [ ] Demo reset cron runs at midnight and data is fresh the next day
- [ ] Org chart renders correctly for a company with multiple departments and staff

---

### 8.8 — Attendance and Hardware Audit

- [ ] QR code is generated for at least one test employee
- [ ] Scanning QR code via the kiosk page calls the REST API and records attendance
- [ ] Geofencing rejects a clock-in submitted with coordinates more than 500m from office
- [ ] Geofenced-but-approved records show the geofence flag in the attendance list
- [ ] Attendance audit trail logs every manual edit with old and new values
- [ ] Live attendance SSE stream updates without page reload
- [ ] If ZKTeco integration was installed: device is syncing attendance records

---

### 8.9 — Print Audit

- [ ] "Print Invoice" button opens PDF in new tab and browser print dialog appears
- [ ] Printed invoice is clean A4 with no navigation elements
- [ ] If thermal printer is installed: "Print Receipt" button sends job to localhost:6500 and receipt prints
- [ ] If print service is not running: fallback to PDF print works without error

---

### 8.10 — Performance Audit

Run these checks and record baseline numbers before going live:

```bash
# Check Redis cache hit rate after 10 minutes of use
docker exec rooibok_redis redis-cli info stats | grep keyspace_hits

# Check slow query log in PostgreSQL
docker exec rooibok_postgres psql -U $DB_USER -d $DB_NAME \
  -c "SELECT query, calls, mean_exec_time FROM pg_stat_statements ORDER BY mean_exec_time DESC LIMIT 10;"

# Enable pg_stat_statements first:
# ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';
```

**Target benchmarks:**

| Page | Target load time |
|------|----------------|
| Dashboard | < 800ms |
| Attendance list (server-side) | < 400ms |
| Employee list (server-side) | < 300ms |
| Payroll list | < 500ms |
| Invoice PDF generation | < 2s |

---

## Phase 9 — Test Phase

**Priority:** Mandatory before production launch  
**Estimated time:** 5–8 days  
**Goal:** End-to-end functional testing of every feature and every user role. Find bugs before clients do.

---

### 9.1 — Test Environment Setup

```bash
# Create a separate test database
docker exec rooibok_postgres psql -U $DB_USER \
  -c "CREATE DATABASE rooibok_test OWNER $DB_USER;"

# Run schema on test database
docker exec rooibok_postgres psql -U $DB_USER -d rooibok_test \
  -f /docker-entrypoint-initdb.d/01_schema.sql

# Run with test environment
APP_ENV=testing DB_NAME=rooibok_test docker compose up -d
```

---

### 9.2 — User Role Test Matrix

Test every role's access to every module. Expected result is shown.

#### Super Admin (`user_type = super_user`)

| Test | Expected result |
|------|----------------|
| Login | 2FA prompt appears |
| Dashboard | Shows all companies, total revenue, active subscriptions |
| Companies list | Shows all registered companies |
| Create membership plan | New plan appears on pricing page |
| Edit landing page content | Change is reflected on public site immediately |
| Manual subscription extension | Company's expiry date is updated |
| View all subscription invoices | Full payment history visible |
| Delete a company | Company and all data removed, staff logins fail |
| Settings → update SMTP | Email sends correctly with new settings |
| Settings → update Stripe keys | Payment succeeds with new keys |
| Run billing:check | Correct reminders fired for test companies |
| Access `/erp/employees` for another company | Should redirect to own desk or 403 |

#### Company Admin (`user_type = company`)

| Test | Expected result |
|------|----------------|
| Login | Sees own company dashboard, not Super Admin panel |
| Add employee | Employee appears in list |
| Edit employee | Changes saved correctly |
| Assign employee to shift | Attendance correctly uses shift times |
| Run payroll | Payslips generated with correct PAYE and NSSF |
| View payslip PDF | PDF downloads correctly |
| Approve leave request | Employee's leave status updates to approved |
| Update attendance manually | Change appears in audit log |
| Extend own subscription via Stripe | Payment processed, expiry updated, invoice generated |
| Extend via MTN MoMo | USSD prompt sent to test phone |
| Download subscription invoice | PDF downloads |
| Switch billing mode | Mode changes without breaking subscription |
| Attempt to access another company's data via URL manipulation | 403 or redirect to own desk |
| Attempt to access Super Admin URL | Redirect to own desk |

#### Staff (`user_type = staff`)

| Test | Expected result |
|------|----------------|
| Login | Sees own profile dashboard only |
| Clock in | Attendance record created |
| Clock out | Record updated with clock-out time |
| Clock in from outside geofence | Record created with geofence_flag = 1 |
| Submit leave request | Appears as pending in own leave list |
| View own payslips | Can see own payslips only |
| Attempt to view another employee's payslip via URL | 403 |
| Submit expense claim | Appears as pending |
| View own attendance | Correct records shown |
| Attempt to access Company Admin menu items | Redirect based on role permissions |

---

### 9.3 — Payment Flow Tests

Run every payment scenario in sandbox before going live:

#### Stripe

```
Test card success: 4242 4242 4242 4242  exp: any future  CVC: any
Test card decline: 4000 0000 0000 0002
Test 3DS required: 4000 0025 0000 3155
Test insufficient funds: 4000 0000 0000 9995
```

| Scenario | Expected result |
|----------|----------------|
| Successful card payment | Subscription extended, invoice generated, email sent |
| Declined card | Error message shown, subscription unchanged |
| Successful auto-renew (webhook) | Subscription extended silently, confirmation email |
| Failed auto-renew retry | Client notified, retry scheduled |
| Cancel auto-renew | `auto_renew = 0`, manual mode activated, expiry unchanged |
| Early renewal (while active) | New expiry = old expiry + plan days |
| Renewal after expiry | New expiry = today + plan days, `is_active = 1` |

#### MTN MoMo (sandbox)

| Scenario | Expected result |
|----------|----------------|
| Valid MTN number | USSD prompt sent, on approval subscription extended |
| Invalid number | Error message, subscription unchanged |
| Timeout (no approval) | Payment shows as failed after 120 seconds |
| Already pending payment | Block duplicate request |

#### Airtel Money (sandbox)

Same test cases as MTN above.

---

### 9.4 — Subscription Lifecycle Tests

Create test companies with manually set expiry dates and run billing check:

```bash
# Test setup (run directly in psql)
-- Company A: 7 days remaining
UPDATE ci_company_membership SET expiry_date = NOW() + INTERVAL '7 days'
WHERE company_id = (SELECT user_id FROM ci_users WHERE email = 'test7@rooibok.co.ug');

-- Company B: 1 day remaining
UPDATE ci_company_membership SET expiry_date = NOW() + INTERVAL '1 day'
WHERE company_id = (SELECT user_id FROM ci_users WHERE email = 'test1@rooibok.co.ug');

-- Company C: expired yesterday
UPDATE ci_company_membership SET expiry_date = NOW() - INTERVAL '1 day', is_active = 1
WHERE company_id = (SELECT user_id FROM ci_users WHERE email = 'testexp@rooibok.co.ug');

-- Run billing check
docker exec rooibok_app php spark billing:check

-- Verify results
SELECT cm.company_id, cm.expiry_date, cm.is_active,
       (SELECT COUNT(*) FROM ci_billing_reminders_log r WHERE r.company_id = cm.company_id) AS reminders_sent
FROM ci_company_membership cm;
```

| Company | Expected result after billing check |
|---------|-----------------------------------|
| 7 days remaining | Day 7 email + SMS + in-app sent. `ci_billing_reminders_log` has 1 row. |
| 1 day remaining | Urgent email + SMS sent. Login shows modal. |
| Expired yesterday | `is_active = 0`. Login redirects to expired page. |
| Run billing check again (same day) | No duplicate reminders sent. |

---

### 9.5 — API Endpoint Tests

Use Postman or curl to test every REST API endpoint:

```bash
# Health check (no auth)
curl https://rooibok.co.ug/api/v1/health
# Expected: {"status":"ok","version":"1.0","db":"connected","redis":"connected"}

# Get JWT token
curl -X POST https://rooibok.co.ug/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"company@test.com","api_secret":"..."}'
# Expected: {"token":"eyJ...","expires_in":86400}

# Clock in
curl -X POST https://rooibok.co.ug/api/v1/attendance/clock-in \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"employee_id":5,"latitude":0.3476,"longitude":32.5825}'
# Expected: {"success":true,"clock_in":"2026-03-14 08:32:00","employee":"John Doe"}

# Invalid token
curl -X GET https://rooibok.co.ug/api/v1/attendance/status \
  -H "Authorization: Bearer invalid_token"
# Expected: 401 {"error":"Invalid or expired token"}

# Rate limit test (run 61 times rapidly)
# Expected: 61st request returns 429 Too Many Requests
```

---

### 9.6 — Browser and Device Compatibility Tests

| Browser / Device | Test |
|----------------|------|
| Chrome (Windows/Mac) | Full functionality |
| Firefox | Full functionality |
| Safari (Mac/iOS) | Full functionality |
| Chrome (Android) | Kiosk page, clock-in, visitor form |
| Edge | Full functionality |
| IE 11 | Not supported — show friendly upgrade message |

---

### 9.7 — Load Test

Before going live, simulate concurrent users:

```bash
# Install Apache Benchmark (ab) or k6
# Simulate 50 concurrent users for 30 seconds on the attendance list

docker run --rm grafana/k6 run - <<EOF
import http from 'k6/http';
export const options = { vus: 50, duration: '30s' };
export default function () {
  http.get('https://rooibok.co.ug/erp/timesheet-attendance', {
    headers: { Cookie: 'ci_session=your_test_session_cookie' }
  });
}
EOF
```

**Target:** P95 response time under 1 second at 50 concurrent users. If it fails, review Redis cache hit rate and add missing indexes.

---

### 9.8 — Go-Live Checklist

Complete every item before switching DNS to production:

**Infrastructure:**
- [ ] Production VPS is provisioned (minimum 4 vCPU, 8GB RAM, 100GB SSD)
- [ ] Docker stack running on production VPS
- [ ] SSL certificate issued and auto-renewal configured
- [ ] Daily database backup cron running
- [ ] Backup files are being uploaded to off-server storage (Backblaze B2 / AWS S3)
- [ ] Server monitoring configured (UptimeRobot or similar — free tier covers 50 monitors)

**Application:**
- [ ] `CI_ENVIRONMENT = production` in `.env.prod`
- [ ] All Stripe keys are live keys (not test keys)
- [ ] MTN MoMo is in production mode (not sandbox)
- [ ] Airtel Money is in production mode (not sandbox)
- [ ] SMS provider is using production credentials
- [ ] SMTP is configured with real email account
- [ ] Super Admin 2FA is set up and backup codes are saved securely

**Content:**
- [ ] Landing page content is accurate and complete
- [ ] Membership plans are set with correct UGX pricing
- [ ] PAYE bands match current URA rates
- [ ] NSSF rates are set correctly
- [ ] Demo company has realistic pre-loaded data
- [ ] Email templates are updated with Rooibok branding

**Final verification:**
- [ ] Phase 8 audit checklist fully completed with no open items
- [ ] Phase 9 test matrix fully completed with all tests passing
- [ ] At least one complete end-to-end walkthrough performed: register → subscribe → add employees → run payroll → generate payslip → renew subscription

---

## Database Reference

### New tables added by this upgrade

| Table | Phase | Purpose |
|-------|-------|---------|
| `ci_attendance_audit` | 2.3 | Logs every manual attendance edit with old/new values and editor identity |
| `ci_subscription_invoices` | 4.5 | Stores all subscription payment invoices with PDF path and transaction reference |
| `ci_billing_reminders_log` | 4.1 | Tracks which reminder (day 7/5/3/2/1) was sent to which company — prevents duplicates |
| `ci_landing_content` | 5.1 | CMS content for public landing page — hero, features, FAQ, testimonials |
| `ci_paye_bands` | 5.3 | Uganda PAYE tax brackets with effective dates — editable from Super Admin UI |
| `ci_expense_categories` | 7.3 | Expense category definitions per company |
| `ci_expenses` | 7.3 | Staff expense claims with receipt upload, approval workflow, payroll integration |
| `ci_broadcasts` | 5.5 | Master broadcast record — subject, body, audience, channels, schedule, status |
| `ci_broadcast_log` | 5.5 | Per-recipient delivery log — personalised content, sent status, open tracking per channel |
| `ci_broadcast_templates` | 5.5 | Saved message templates for reuse — scoped to company or system-wide |

### New columns added to existing tables

| Table | Column(s) | Phase | Notes |
|-------|-----------|-------|-------|
| `ci_users` | `totp_secret`, `totp_enabled` | 2.1 | 2FA support |
| `ci_users` | `is_demo` | 5.2 | Flags demo account |
| `ci_company_membership` | `billing_mode`, `stripe_customer_id`, `stripe_sub_id`, `auto_renew` | 3.2 | Stripe subscription billing |
| `ci_timesheet` | `geofence_flag`, `clock_in_latitude_dec`, `clock_in_longitude_dec`, `clock_out_latitude_dec`, `clock_out_longitude_dec` | 2.4 | GPS geofencing |
| `ci_erp_settings` | `nssf_employee_rate`, `nssf_employer_rate`, `nssf_enabled` | 5.3 | NSSF rates |
| `ci_erp_settings` | `stripe_secret_key`, `stripe_publishable_key`, `stripe_webhook_secret`, `stripe_mode`, `stripe_active` | 1.4 | Stripe config — encrypted at rest |
| `ci_erp_settings` | `mtn_subscription_key`, `mtn_api_user`, `mtn_api_key`, `mtn_environment`, `mtn_active` | 1.4 | MTN MoMo config — encrypted at rest |
| `ci_erp_settings` | `airtel_client_id`, `airtel_client_secret`, `airtel_environment`, `airtel_active` | 1.4 | Airtel Money config — encrypted at rest |
| `ci_erp_settings` | `sms_provider`, `sms_username`, `sms_api_key`, `sms_sender_id`, `sms_active` | 1.4 | SMS provider config — encrypted at rest |
| `ci_erp_settings` | `jwt_secret`, `jwt_ttl_hours`, `api_active`, `api_rate_limit` | 1.4 | JWT / REST API config |
| `ci_erp_settings` | `default_geofence_radius`, `billing_reminder_active`, `billing_reminder_days` | 1.4 | Attendance and billing config |

## Service Providers Reference

All credentials below are configured through the Super Admin portal UI (Settings tabs), stored encrypted in `ci_erp_settings`, and loaded at runtime. None appear in `.env`. This table is a registration guide — it tells you where to sign up for each service and what credentials you will receive to enter into the settings UI.

### Email — SMTP

| Setting | Value / Where to get it |
|---------|------------------------|
| Provider | Any SMTP server. Recommended: Google Workspace (professional sender address) |
| Register at | workspace.google.com — from UGX 6,000/month per account |
| Free alternative | Gmail SMTP — 500 emails/day limit (fine for internal HR, tight for bulk broadcasts) |
| SMTP host | `smtp.gmail.com` (Gmail) or `smtp.googlemail.com` |
| SMTP port | `465` (SSL) or `587` (TLS) |
| Username | Your full email address |
| Password | Gmail: generate an App Password at myaccount.google.com/apppasswords |
| From name | Enter `Rooibok HR System` in the settings UI |

### SMS — Africa's Talking (recommended)

| Setting | Value / Where to get it |
|---------|------------------------|
| Register at | africastalking.com — free sandbox, instant approval |
| Production | Requires identity verification and deposit for airtime credit |
| Pricing Uganda | Approximately UGX 35–60 per SMS depending on network and volume |
| API username | Shown on your Africa's Talking dashboard after registration |
| API key | Generate from Settings → API Key on the dashboard |
| Sender ID | Apply for a custom sender ID (e.g. `RooibokHR`) through their portal — takes 1–3 business days for Uganda approval. Default is a shortcode. |
| Sandbox testing | Use username `sandbox` and any API key — messages go to the AT simulator, not real phones |

### Stripe — Card Payments and Auto-Renew

| Setting | Value / Where to get it |
|---------|------------------------|
| Register at | stripe.com — free to create account, pay per transaction |
| Uganda | Stripe is available in Uganda as of 2024. Payouts to Ugandan bank accounts are supported. |
| Pricing | 2.9% + $0.30 per successful charge (international card rate may differ) |
| Secret key | stripe.com → Developers → API keys → Secret key (starts `sk_live_...`) |
| Publishable key | Same page — Publishable key (starts `pk_live_...`) |
| Webhook secret | stripe.com → Developers → Webhooks → Add endpoint → your webhook URL → Signing secret |
| Webhook URL to register | `https://rooibok.co.ug/api/v1/webhooks/stripe` |
| Events to listen for | `invoice.payment_succeeded`, `invoice.payment_failed`, `customer.subscription.deleted` |
| Test mode | Use `sk_test_...` and `pk_test_...` keys during development — toggle in settings UI |

### MTN Mobile Money Uganda

| Setting | Value / Where to get it |
|---------|------------------------|
| Register at | momodeveloper.mtn.com — free sandbox, immediate access |
| Production | Contact MTN Uganda business team for merchant account approval |
| API subscription key | momodeveloper.mtn.com → My Apps → Collections → Subscribe → Primary key |
| API user | Created via API call to sandbox: `POST /v1_0/apiuser` with your subscription key |
| API key | Created via API call: `POST /v1_0/apiuser/{apiUserId}/apikey` |
| Environment | Set `sandbox` during development, `mtncongo` or `mtnuganda` for production |
| Callback URL to configure | `https://rooibok.co.ug/api/v1/webhooks/mtn` — enter in the settings UI |
| Sandbox test number | Any number — USSD prompt goes to MTN sandbox simulator |

### Airtel Money Uganda

| Setting | Value / Where to get it |
|---------|------------------------|
| Register at | developers.airtel.africa — free sandbox |
| Production | Contact Airtel Uganda for merchant agreement |
| Client ID | developers.airtel.africa → My Apps → Create app → Client ID |
| Client secret | Same page — Client Secret |
| Environment | `sandbox` for testing, `prod` for production |
| Callback URL | `https://rooibok.co.ug/api/v1/webhooks/airtel` — enter in settings UI |

---

## Environment Variables Reference

### What stays in `.env` — infrastructure only

The `.env` file is minimal by design. Everything operational (API keys, credentials, feature toggles) lives in the database and is editable from the Super Admin UI. The `.env` file contains only three categories of variables that must be set before the application can start.

### .env.example (commit this to git — it documents required variables without values)

```env
# ─────────────────────────────────────────────
# INFRASTRUCTURE — set once, never change again
# ─────────────────────────────────────────────

# Application environment
APP_ENV=development
APP_BASEURL=http://localhost:8080

# PostgreSQL — connection only, no API keys here
DB_NAME=rooibok_hr
DB_USER=rooibok_user
DB_PASS=change_this_to_strong_password
DB_ROOT_PASS=change_this_root_password

# Redis — connection only
REDIS_HOST=redis

# Encryption master key — protects API keys stored in the database
# Generate with: php spark key:generate
# NEVER put this in the database or expose it in any UI
ENCRYPTION_KEY=

# ─────────────────────────────────────────────
# PGADMIN — dev only, not used in production
# ─────────────────────────────────────────────
PGADMIN_EMAIL=admin@rooibok.co.ug
PGADMIN_PASS=change_this

# ─────────────────────────────────────────────
# EVERYTHING ELSE IS CONFIGURED IN THE UI
# ─────────────────────────────────────────────
# Stripe keys      → Super Admin → Settings → Stripe tab
# MTN MoMo keys   → Super Admin → Settings → MTN Mobile Money tab
# Airtel keys      → Super Admin → Settings → Airtel Money tab
# SMS credentials  → Super Admin → Settings → SMS tab
# SMTP credentials → Super Admin → Settings → Email/SMTP tab
# JWT secret       → Super Admin → Settings → API & Security tab
# PAYE tax bands   → Super Admin → Settings → Tax tab
# Geofence radius  → Super Admin → Settings → Attendance tab
```

### Production `.env.prod` — add these additional values

```env
APP_ENV=production
APP_BASEURL=https://rooibok.co.ug

# Same DB/Redis/Encryption variables as above but with production values
# All other credentials are managed through the Super Admin UI
```

> Never commit `.env.prod` to git. Add it to `.gitignore`. Copy it to the server manually via SSH or a secrets manager.

---


---

## Phase 10 — Data Archive Subsystem

**Priority:** Medium — implement after Phases 0–7 are stable in production  
**Estimated time:** 10–15 days  
**Goal:** Three-tier data archive serving four purposes simultaneously — performance (offload old data from live tables), legal compliance (7-year HR record retention), offboarding (sealed company snapshots on cancellation), and marketing intelligence (structured contact database for campaigns and research). Super Admin access only.

---

### 10.1 — Architecture Overview

The archive operates across three tiers. Each tier serves a different access pattern and cost profile. Data flows forward through tiers automatically — it never moves backward except via a deliberate Super Admin restore action.

**Tier 1 — Live PostgreSQL (hot):** The main `rooibok_hr` database. Full query speed, Redis cached, hit on every request. Data stays here while it is operationally relevant. Retention thresholds: attendance 24 months, payroll 36 months, system logs 12 months, broadcasts 6 months.

**Tier 2 — Cold archive database (warm):** A separate `rooibok_archive` PostgreSQL database in the same Docker stack. Schema mirrors live tables with additional archive metadata columns. Queryable by Super Admin via the archive portal. Not in the application's request path — the live app never queries it.

**Tier 3 — File vault (cold):** Backblaze B2 or AWS S3. Sealed, immutable ZIP bundles. One bundle per company offboarding event and one per annual compliance export. SHA-256 checksummed. Downloadable by Super Admin. Not queryable — these are for legal custody and disaster recovery.

**Marketing intelligence layer:** A set of views and extracted tables built on top of Tier 2. Structured contact and company data segmented by region, plan, industry, employee count, and subscription history. Used for re-engagement campaigns, product research, and market analysis.

---

### 10.2 — Docker Compose Addition

Add the archive database as a second PostgreSQL service:

```yaml
# Add to compose.yml under services:

  postgres_archive:
    image: postgres:16-alpine
    container_name: rooibok_archive
    restart: unless-stopped
    environment:
      POSTGRES_DB:       rooibok_archive
      POSTGRES_USER:     ${DB_USER}
      POSTGRES_PASSWORD: ${DB_PASS}
    volumes:
      - pg_archive_data:/var/lib/postgresql/data
      - ./docker/postgres/archive_schema.sql:/docker-entrypoint-initdb.d/01_archive_schema.sql
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USER} -d rooibok_archive"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - rooibok_net

# Add to volumes:
  pg_archive_data:
```

Add archive DB connection to CI4:

```php
// app/Config/Database.php — add second connection
public array $archive = [
    'hostname' => 'postgres_archive',
    'username' => '',
    'password' => '',
    'database' => 'rooibok_archive',
    'DBDriver' => 'Postgre',
    'port'     => 5432,
];
```

Usage in archive controllers:

```php
$db = db_connect('archive');
$results = $db->table('arc_attendance')->where('company_id', $id)->get();
```

---

### 10.3 — Archive Schema (Tier 2)

```sql
-- ─────────────────────────────────────────────────────
-- Archive metadata — added to every archive table
-- ─────────────────────────────────────────────────────

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
    company_type        VARCHAR(100),       -- industry
    registration_no     VARCHAR(100),
    employee_count      INTEGER,            -- at time of archiving
    plan_name           VARCHAR(100),
    plan_tier           VARCHAR(20),        -- starter, growth, enterprise
    subscription_start  DATE,
    subscription_end    DATE,
    total_months_paid   INTEGER,
    total_revenue_ugx   NUMERIC(14,2),
    cancellation_reason VARCHAR(200),
    archive_reason      VARCHAR(50),        -- cancelled, offboarded, manual
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    vault_bundle_path   VARCHAR(500),       -- Tier 3 file path when generated
    vault_checksum      VARCHAR(64),        -- SHA-256
    consent_given       SMALLINT DEFAULT 0, -- marketing consent from ToS at registration
    consent_date        TIMESTAMP WITH TIME ZONE,
    unsubscribed        SMALLINT DEFAULT 0,
    unsubscribed_at     TIMESTAMP WITH TIME ZONE
);

-- Archived employees (terminated + company-level archiving)
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
    archive_reason      VARCHAR(50)         -- terminated, resigned, company_cancelled
);

-- Archived attendance (age-based rotation from live table)
CREATE TABLE arc_attendance (
    arc_id              SERIAL PRIMARY KEY,
    source_record_id    INTEGER NOT NULL,
    source_company_id   INTEGER NOT NULL,
    employee_id         INTEGER,
    employee_name       VARCHAR(200),       -- denormalised — employee may be deleted
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

-- Archived system logs (broadcasts, audit trail, billing history)
CREATE TABLE arc_system_logs (
    arc_id              SERIAL PRIMARY KEY,
    source_company_id   INTEGER,
    log_type            VARCHAR(50),        -- broadcast, audit_edit, billing, login
    description         TEXT,
    metadata            JSONB,
    log_date            TIMESTAMP WITH TIME ZONE,
    archived_at         TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Marketing intelligence contacts (extracted view + maintained table)
CREATE TABLE arc_contacts (
    contact_id          SERIAL PRIMARY KEY,
    snapshot_id         INTEGER REFERENCES arc_company_snapshots(snapshot_id),
    contact_type        VARCHAR(20) NOT NULL,   -- company_admin, crm_client
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
    status              VARCHAR(20),            -- active, trial, expired, cancelled
    last_seen           DATE,
    consent_given       SMALLINT DEFAULT 0,
    unsubscribed        SMALLINT DEFAULT 0,
    tags                TEXT[],                 -- PostgreSQL array: ['kampala','healthcare','churned']
    created_at          TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for fast marketing queries
CREATE INDEX idx_arc_contacts_region   ON arc_contacts(country, city);
CREATE INDEX idx_arc_contacts_status   ON arc_contacts(status, consent_given);
CREATE INDEX idx_arc_contacts_plan     ON arc_contacts(plan_tier, employee_count);
CREATE INDEX idx_arc_contacts_industry ON arc_contacts(industry);
CREATE INDEX idx_arc_contacts_email    ON arc_contacts(email);
```

---

### 10.4 — Archiving Triggers

#### Time-based (monthly cron — first day of each month at 02:00)

```bash
# crontab
0 2 1 * * docker exec rooibok_app php spark archive:rotate
```

`php spark archive:rotate` runs the following in order:

```php
// app/Commands/ArchiveRotate.php
class ArchiveRotate extends BaseCommand {
    public function run(array $params): void {
        $this->archiveOldAttendance();     // > 24 months
        $this->archiveOldPayroll();        // > 36 months
        $this->archiveOldSystemLogs();     // > 12 months
        $this->archiveOldBroadcasts();     // > 6 months
        $this->updateStorageDashboard();   // recalculate tier sizes
        $this->log('Monthly rotation complete');
    }

    private function archiveOldAttendance(): void {
        $cutoff = date('Y-m-d', strtotime('-24 months'));
        $liveDb = db_connect('default');
        $archDb = db_connect('archive');

        // Fetch rows to archive
        $rows = $liveDb->table('ci_timesheet')
            ->where('attendance_date <', $cutoff)
            ->get()->getResultArray();

        if (empty($rows)) return;

        // Denormalise employee name before moving (employee record may be deleted later)
        foreach ($rows as &$row) {
            $user = $liveDb->table('ci_users')
                ->select('first_name, last_name')
                ->where('user_id', $row['employee_id'])
                ->get()->getRowArray();
            $row['employee_name'] = $user
                ? $user['first_name'] . ' ' . $user['last_name']
                : 'Unknown';
            $row['source_record_id']  = $row['time_attendance_id'];
            $row['source_company_id'] = $row['company_id'];
            $row['archived_at']       = date('Y-m-d H:i:s');
        }

        // Insert into archive
        $archDb->table('arc_attendance')->insertBatch($rows);

        // Delete from live (only after successful archive insert)
        $ids = array_column($rows, 'time_attendance_id');
        $liveDb->table('ci_timesheet')
            ->whereIn('time_attendance_id', $ids)
            ->delete();

        $this->log("Archived {$archDb->affectedRows()} attendance records older than $cutoff");
    }
}
```

#### Event-based — employee termination

```php
// app/Controllers/Erp/Leaving.php — existing controller
// Add this call inside the process_leaving() method after status update:

private function archiveTerminatedEmployee(int $userId, string $reason): void {
    $queue = new \App\Libraries\Queue();
    $queue->push('archive', [
        'action'     => 'archive_employee',
        'user_id'    => $userId,
        'reason'     => $reason,
        'triggered'  => 'termination',
    ]);
    // Queue worker handles the actual archive operation asynchronously
}
```

#### Event-based — company cancellation / offboarding

Triggered when: a company's subscription expires and remains unpaid for 90 days, OR when Super Admin manually triggers offboarding from the company detail page.

```php
// app/Commands/ArchiveCompany.php
class ArchiveCompany extends BaseCommand {
    public function archiveFullCompany(int $companyId, string $reason): void {
        // 1. Create snapshot record in arc_company_snapshots
        $this->createCompanySnapshot($companyId, $reason);

        // 2. Archive all employees
        $this->archiveAllEmployees($companyId);

        // 3. Archive all attendance, payroll, leave
        $this->archiveHrRecords($companyId);

        // 4. Archive system logs
        $this->archiveSystemLogs($companyId);

        // 5. Extract contacts into arc_contacts
        $this->extractContacts($companyId);

        // 6. Generate Tier 3 vault bundle (queued — can take minutes)
        $queue = new \App\Libraries\Queue();
        $queue->push('archive_vault', [
            'action'     => 'generate_bundle',
            'company_id' => $companyId,
        ]);

        // 7. Log completion
        $this->log("Company $companyId fully archived. Reason: $reason");
    }
}
```

---

### 10.5 — Tier 3 Vault Bundle Generation

Each company offboarding event generates one sealed ZIP bundle:

```
RBHR_CompanyName_2026-03-14.zip
├── manifest.json              (bundle metadata, company details, record counts, SHA-256)
├── company_profile.json       (company settings, admin details, plan history)
├── employees/
│   ├── employees.json         (all employee records)
│   └── {employee_id}_profile.pdf   (individual payslip-style summary per employee)
├── attendance/
│   └── attendance_{year}.json (one file per year)
├── payroll/
│   └── payroll_{year}.json
│   └── payslips_{year}.pdf    (all payslips in one PDF per year)
├── leave/
│   └── leave_history.json
├── invoices/
│   └── subscription_invoices.json
│   └── invoice_{number}.pdf   (each subscription invoice)
└── logs/
    └── system_logs.json
```

**Bundle generation — queue worker:**

```php
// In queue worker — archive_vault tube handler
case 'generate_bundle':
    $companyId = $job['company_id'];
    $tmpDir    = "/tmp/archive_{$companyId}_" . time();
    mkdir($tmpDir, 0755, true);

    // Write all JSON files
    $this->writeJson($tmpDir, 'company_profile.json', $this->getCompanyProfile($companyId));
    $this->writeJson($tmpDir, 'employees/employees.json', $this->getEmployees($companyId));
    // ... (repeat for all data types)

    // Generate PDFs using DOMPDF
    $this->generatePayslipsPdf($tmpDir, $companyId);
    $this->generateInvoicesPdf($tmpDir, $companyId);

    // Create ZIP
    $zipPath = "/var/www/html/storage/vault/{$companyId}/";
    mkdir($zipPath, 0755, true);
    $zipFile = $zipPath . "RBHR_{$companyId}_" . date('Y-m-d') . ".zip";

    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE);
    $this->addDirToZip($zip, $tmpDir);
    $zip->close();

    // Compute checksum
    $checksum = hash_file('sha256', $zipFile);

    // Upload to Backblaze B2
    $b2Path = $this->uploadToB2($zipFile, "vault/{$companyId}/" . basename($zipFile));

    // Update snapshot record
    $archDb->table('arc_company_snapshots')
        ->where('source_company_id', $companyId)
        ->set(['vault_bundle_path' => $b2Path, 'vault_checksum' => $checksum])
        ->update();

    // Cleanup temp dir
    exec("rm -rf {$tmpDir}");
    break;
```

**Backblaze B2 credentials** — stored in `ci_erp_settings` (editable in Super Admin Settings → Archive tab):

```sql
ALTER TABLE ci_erp_settings ADD COLUMN b2_account_id      VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN b2_application_key VARCHAR(200);
ALTER TABLE ci_erp_settings ADD COLUMN b2_bucket_name     VARCHAR(100);
ALTER TABLE ci_erp_settings ADD COLUMN b2_active          SMALLINT DEFAULT 0;
```

**B2 pricing:** Backblaze B2 charges $0.006 per GB per month for storage and $0.01 per GB for downloads. A typical company archive bundle is 50–200MB. Storing 100 company archives = roughly 10–20GB = $0.06–$0.12 per month. Essentially free.

---

### 10.6 — Marketing Intelligence Extraction

Contacts are extracted into `arc_contacts` as part of the company archiving pipeline. They are also refreshed from live data on the first of each month so that active companies appear in the contact database with current status.

```php
private function extractContacts(int $companyId): void {
    $liveDb   = db_connect('default');
    $archDb   = db_connect('archive');
    $snapshot = $archDb->table('arc_company_snapshots')
        ->where('source_company_id', $companyId)
        ->orderBy('archived_at', 'DESC')
        ->limit(1)->get()->getRowArray();

    // Get company admin
    $admin = $liveDb->table('ci_users')
        ->where('user_id', $companyId)
        ->where('user_type', 'company')
        ->get()->getRowArray();

    if (!$admin) return;

    $settings = $liveDb->table('ci_companysettings')
        ->where('company_id', $companyId)->get()->getRowArray();

    $archDb->table('arc_contacts')->insert([
        'snapshot_id'         => $snapshot['snapshot_id'] ?? null,
        'contact_type'        => 'company_admin',
        'first_name'          => $admin['first_name'],
        'last_name'           => $admin['last_name'],
        'full_name'           => $admin['first_name'] . ' ' . $admin['last_name'],
        'email'               => $admin['email'],
        'phone'               => $admin['phone'] ?? '',
        'company_name'        => $admin['company_name'],
        'country'             => $admin['country_name'] ?? '',
        'city'                => $settings['city'] ?? '',
        'region'              => $settings['region'] ?? '',
        'industry'            => $admin['company_type_name'] ?? '',
        'employee_count'      => $snapshot['employee_count'] ?? 0,
        'plan_tier'           => $snapshot['plan_tier'] ?? '',
        'subscription_months' => $snapshot['total_months_paid'] ?? 0,
        'status'              => $snapshot['archive_reason'] === 'cancelled' ? 'cancelled' : 'active',
        'last_seen'           => date('Y-m-d'),
        'consent_given'       => $admin['marketing_consent'] ?? 0,
        'tags'                => '{' . implode(',', $this->generateTags($snapshot)) . '}',
        'created_at'          => date('Y-m-d H:i:s'),
    ]);
}

private function generateTags(array $snapshot): array {
    $tags = [];
    if ($snapshot['city']) $tags[] = strtolower($snapshot['city']);
    if ($snapshot['industry']) $tags[] = strtolower(str_replace(' ', '_', $snapshot['industry']));
    if ($snapshot['plan_tier']) $tags[] = $snapshot['plan_tier'];
    if ($snapshot['archive_reason'] === 'cancelled') $tags[] = 'churned';
    if (($snapshot['total_months_paid'] ?? 0) >= 12) $tags[] = 'long_term';
    if (($snapshot['employee_count'] ?? 0) > 50) $tags[] = 'large_company';
    return $tags;
}
```

---

### 10.7 — Super Admin Archive Portal

**Route:** `erp/archive` — accessible only when `user_type = super_user`

**Sub-sections:**

**Dashboard** (`erp/archive`) — storage usage: Tier 1 live DB size, Tier 2 archive DB size, Tier 3 vault files count and total GB. Record counts per category. Last archive job run time and results.

**Company archives** (`erp/archive/companies`) — list of all archived companies. Columns: company name, admin name, admin email, plan, months paid, archive date, reason. Click a row to view full snapshot. Download vault bundle button. "Restore to live" button (Super Admin only — restores company to active state).

**HR record search** (`erp/archive/search`) — search across `arc_attendance`, `arc_payroll`, `arc_leaves`, `arc_employees`. Filters: company, employee name, date range, record type. Useful for responding to legal requests for specific employee records.

**Contact intelligence** (`erp/archive/contacts`) — the marketing database. Full segmentation UI:

```
[ Filter: Status ] [ Filter: Region/City ] [ Filter: Plan tier ]
[ Filter: Industry ] [ Filter: Employee count ] [ Filter: Consent: yes ]

Results: 142 contacts matching filters

[ Export CSV ] [ Send broadcast ]
```

"Send broadcast" opens a modal with the broadcast composer (from Phase 5.5), pre-populated with the filtered recipient list. Sends via the existing broadcast engine.

**Vault files** (`erp/archive/vault`) — list of all Tier 3 bundles. Company name, file size, date generated, SHA-256 checksum. Download button (streams from B2 with a signed URL — never exposes B2 credentials to browser).

**Settings** (`erp/archive/settings`) — configure retention periods per data type, Backblaze B2 credentials, auto-archive on cancellation toggle, minimum days before auto-archive after expiry.

**Controller:** `app/Controllers/Erp/Archive.php`

```php
class Archive extends BaseController {
    // All methods check user_type === 'super_user' first
    public function index(): string { }          // dashboard
    public function companies(): string { }       // company archive list
    public function company_detail(): string { }  // full snapshot view
    public function search(): string { }          // cross-table HR record search
    public function contacts(): string { }        // marketing intelligence
    public function export_contacts(): void { }   // CSV export
    public function send_to_contacts(): ResponseInterface { } // broadcast to segment
    public function vault(): string { }           // file vault listing
    public function download_bundle(): void { }   // signed B2 download
    public function restore_company(): ResponseInterface { } // restore to live
    public function settings(): string { }
    public function trigger_archive(): ResponseInterface { } // manual archive trigger
}
```

---

### 10.8 — Restore to Live System

Super Admin can restore a fully archived company back to the live system. Use cases: client renews after being archived, data recovery, mistake correction.

```php
public function restore_company(int $companyId): ResponseInterface {
    // 1. Re-create user record from snapshot
    // 2. Re-create company settings
    // 3. Restore employees from arc_employees
    // 4. Restore attendance, payroll, leave from archive tables
    // 5. Set new expiry date (Super Admin sets this — they pay first)
    // 6. Mark snapshot as restored in arc_company_snapshots
    // 7. Send welcome-back email to company admin

    // Note: does NOT delete archive records — both live and archive coexist
    // This gives you a full audit trail
}
```

---

### 10.9 — Retention Policy Table

Configurable from Super Admin Settings → Archive:

| Data type | Default retention in live DB | Archive retention | Legal basis |
|-----------|------------------------------|------------------|-------------|
| Attendance records | 24 months | 7 years | Uganda Employment Act |
| Payroll records | 36 months | 7 years | Uganda tax law |
| Employee profiles | Until terminated | 7 years post-termination | Uganda Employment Act |
| Leave records | 24 months | 7 years | Uganda Employment Act |
| System audit logs | 12 months | 3 years | Internal policy |
| Broadcast logs | 6 months | 1 year | Internal policy |
| Billing/subscription | 36 months (live) | 7 years | Uganda tax law |
| Company snapshots | N/A | 7 years | Contractual |
| Marketing contacts | N/A (archive only) | Until unsubscribed | DPPA 2019 consent |

---

### 10.10 — Privacy and Consent (Uganda DPPA 2019)

The Uganda Data Protection and Privacy Act 2019 applies to all personal data processed by Rooibok HR System.

**At registration — add to Terms of Service and registration form:**

```
[ ✓ ] I agree to the Terms of Service and Privacy Policy.
      I understand that my contact information may be retained after account
      closure for service records and, if consented below, for product updates.

[ _ ] I consent to receive product updates, re-engagement communications,
      and market research surveys from Rooibok HR via email and SMS.
      I can unsubscribe at any time.
```

Store `marketing_consent = 1` and `consent_date` on the user record. Copy to `arc_contacts.consent_given` and `consent_date` when archiving.

**Every marketing email must contain:**
- Sender identification (Rooibok HR System, rooibok.co.ug)
- Clear reason for contact ("You previously used Rooibok HR System")
- One-click unsubscribe link (`/unsubscribe?token={encoded_contact_id}`)

**Unsubscribe handler:**

```php
// app/Controllers/Unsubscribe.php — no auth required
public function index(): string {
    $token     = $this->request->getGet('token');
    $contactId = udecode($token);
    $archDb    = db_connect('archive');
    $archDb->table('arc_contacts')->where('contact_id', $contactId)->set([
        'unsubscribed'    => 1,
        'unsubscribed_at' => date('Y-m-d H:i:s'),
    ])->update();
    return view('frontend/unsubscribed'); // simple confirmation page
}
```

**The broadcast engine (Phase 5.5) already filters `unsubscribed = 0` before sending** — add this filter when the audience source is `arc_contacts`.

---

### 10.10 — Export Formats (All Document Formats)

Every data view in the archive portal supports download in multiple formats. The export button is present on: company archive list, HR record search results, contact intelligence, payroll records, attendance records, and broadcast/audit logs.

#### Supported export formats

| Format | Use case | Library |
|--------|----------|---------|
| PDF | Human-readable reports, legal submissions, printable payslip bundles | DOMPDF (already in stack) |
| Excel (.xlsx) | Finance team analysis, payroll reconciliation, pivot tables | PhpSpreadsheet |
| CSV | Import into other systems, bulk upload, simple data exchange | PHP native (fputcsv) |
| JSON | Developer integration, API consumption, structured data archiving | PHP native (json_encode) |
| Word (.docx) | Formal HR letters, employee history documents, compliance reports | PhpWord |
| ZIP bundle | Everything at once — all formats in one download | PHP ZipArchive |

#### Installation

```bash
# Add to composer.json
composer require phpoffice/phpspreadsheet
composer require phpoffice/phpword
# DOMPDF and ZipArchive already in stack
```

#### Export controller — unified handler

```php
// app/Controllers/Erp/ArchiveExport.php

class ArchiveExport extends BaseController {

    /**
     * Unified export endpoint.
     * Route: GET erp/archive/export?type={contacts|attendance|payroll|companies}&format={pdf|xlsx|csv|json|docx|zip}&{filters}
     */
    public function export(): void {
        // Super Admin only
        if ($this->userType !== 'super_user') {
            return redirect()->to(site_url('erp/desk'));
        }

        $type    = $this->request->getGet('type');    // contacts, attendance, payroll, companies
        $format  = $this->request->getGet('format');  // pdf, xlsx, csv, json, docx, zip
        $filters = $this->request->getGet();          // all other GET params are filters

        $data    = $this->fetchData($type, $filters);
        $label   = 'Rooibok_HR_' . ucfirst($type) . '_' . date('Y-m-d');

        match($format) {
            'pdf'   => $this->exportPdf($data, $type, $label),
            'xlsx'  => $this->exportXlsx($data, $type, $label),
            'csv'   => $this->exportCsv($data, $type, $label),
            'json'  => $this->exportJson($data, $label),
            'docx'  => $this->exportDocx($data, $type, $label),
            'zip'   => $this->exportZip($data, $type, $label),   // all formats in one ZIP
            default => $this->exportCsv($data, $type, $label),
        };
    }

    // ─── PDF ────────────────────────────────────────────────────────────────
    private function exportPdf(array $data, string $type, string $label): void {
        $html = view('erp/archive/exports/pdf_' . $type, [
            'data'       => $data,
            'generated'  => date('d M Y H:i'),
            'title'      => 'Rooibok HR System — ' . ucfirst($type) . ' Export',
        ]);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($label . '.pdf', ['Attachment' => true]);
        exit;
    }

    // ─── Excel ──────────────────────────────────────────────────────────────
    private function exportXlsx(array $data, string $type, string $label): void {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ucfirst($type));

        // Header row — bold
        $headers = array_keys($data[0] ?? []);
        foreach ($headers as $col => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, strtoupper(str_replace('_', ' ', $header)));
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Data rows
        foreach ($data as $rowIdx => $row) {
            foreach (array_values($row) as $col => $value) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($rowIdx + 2);
                $sheet->setCellValue($cell, $value);
            }
        }

        // Auto-size columns
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // Add Rooibok branding in header row background
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1D9E75'); // Rooibok teal

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $label . '.xlsx"');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ─── CSV ────────────────────────────────────────────────────────────────
    private function exportCsv(array $data, string $type, string $label): void {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $label . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM — ensures Excel opens Ugandan names correctly
        $out = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($out, array_keys($data[0])); // headers
            foreach ($data as $row) {
                fputcsv($out, $row);
            }
        }
        fclose($out);
        exit;
    }

    // ─── JSON ───────────────────────────────────────────────────────────────
    private function exportJson(array $data, string $label): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $label . '.json"');
        echo json_encode([
            'exported_at' => date('Y-m-d\TH:i:sP'),
            'system'      => 'Rooibok HR System',
            'record_count'=> count($data),
            'data'        => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Word / DOCX ────────────────────────────────────────────────────────
    private function exportDocx(array $data, string $type, string $label): void {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection();

        // Title
        $section->addText(
            'Rooibok HR System — ' . ucfirst($type) . ' Report',
            ['bold' => true, 'size' => 16],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            'Generated: ' . date('d M Y H:i') . ' | Records: ' . count($data),
            ['size' => 9, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addTextBreak();

        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $table   = $section->addTable(['borderSize' => 6, 'borderColor' => 'cccccc', 'cellMargin' => 80]);

            // Header row
            $table->addRow();
            foreach ($headers as $header) {
                $cell = $table->addCell(2000, ['bgColor' => '1D9E75']);
                $cell->addText(strtoupper(str_replace('_', ' ', $header)), ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
            }

            // Data rows — alternate row colours
            foreach ($data as $i => $row) {
                $table->addRow();
                $bg = ($i % 2 === 0) ? 'FFFFFF' : 'F5F5F5';
                foreach ($row as $value) {
                    $cell = $table->addCell(2000, ['bgColor' => $bg]);
                    $cell->addText((string) $value, ['size' => 9]);
                }
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $label . '.docx"');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    // ─── ZIP — all formats in one bundle ────────────────────────────────────
    private function exportZip(array $data, string $type, string $label): void {
        $tmpDir = sys_get_temp_dir() . '/' . $label . '_' . time();
        mkdir($tmpDir);

        // Write CSV
        $csvPath = $tmpDir . '/' . $label . '.csv';
        $f = fopen($csvPath, 'w');
        fputs($f, "\xEF\xBB\xBF");
        if (!empty($data)) {
            fputcsv($f, array_keys($data[0]));
            foreach ($data as $row) fputcsv($f, $row);
        }
        fclose($f);

        // Write JSON
        file_put_contents($tmpDir . '/' . $label . '.json', json_encode([
            'exported_at'  => date('Y-m-d\TH:i:sP'),
            'system'       => 'Rooibok HR System',
            'record_count' => count($data),
            'data'         => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Write XLSX (reuse logic)
        ob_start();
        $this->exportXlsx($data, $type, $label);
        file_put_contents($tmpDir . '/' . $label . '.xlsx', ob_get_clean());

        // Create ZIP
        $zipPath = sys_get_temp_dir() . '/' . $label . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach (glob($tmpDir . '/*') as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $label . '.zip"');
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath);
        exec("rm -rf {$tmpDir}");
        exit;
    }
}
```

#### Export buttons in the UI

Every list view in the archive portal has a consistent export toolbar:

```html
<!-- Rendered above every archive data table -->
<div class="export-bar" style="display:flex;gap:8px;margin-bottom:12px;">
    <span style="font-size:12px;color:var(--color-text-secondary);align-self:center;">Export:</span>
    <a href="?type=contacts&format=pdf&{current_filters}"   class="btn btn-sm btn-light-danger">  PDF  </a>
    <a href="?type=contacts&format=xlsx&{current_filters}"  class="btn btn-sm btn-light-success"> Excel </a>
    <a href="?type=contacts&format=csv&{current_filters}"   class="btn btn-sm btn-light-info">    CSV   </a>
    <a href="?type=contacts&format=json&{current_filters}"  class="btn btn-sm btn-light-warning"> JSON  </a>
    <a href="?type=contacts&format=docx&{current_filters}"  class="btn btn-sm btn-light-primary"> Word  </a>
    <a href="?type=contacts&format=zip&{current_filters}"   class="btn btn-sm btn-dark">          All formats (ZIP) </a>
</div>
```

The `{current_filters}` are the same filter parameters currently applied to the table — so what you see in the table is exactly what you download. Filtered to Kampala + Healthcare + consent = yes? Your export contains only those 14 contacts.

#### Also applied to live HR reports (not just archive)

The same export class is also wired into live report views so Company Admins can download their own reports:

| Live report | Formats available |
|-------------|------------------|
| Monthly attendance report | PDF, Excel, CSV |
| Payroll report | PDF, Excel, CSV, Word |
| Leave summary | PDF, Excel, CSV |
| Employee list | PDF, Excel, CSV |
| Subscription invoices | PDF only (already generated by DOMPDF) |
| Broadcast delivery report | Excel, CSV |

Company Admins see only their own company data. The same export controller is reused — just pointed at the live database with `company_id` isolation enforced.

---

### 10.11 — Legal Pages (Cookie Policy, Privacy Policy, Terms of Service)

These pages are part of the public landing page and must clearly state the data practices described throughout this upgrade plan. They are database-driven (editable from Super Admin → Landing Page CMS, section 5.1) with a base template that ensures all legally required disclosures are present.

All three pages are accessible from the public footer at `rooibok.co.ug/privacy`, `rooibok.co.ug/cookies`, and `rooibok.co.ug/terms`.

**Routes to add:**

```php
$routes->get('privacy',  'Home::privacy');
$routes->get('cookies',  'Home::cookies');
$routes->get('terms',    'Home::terms');
```

---

#### Privacy Policy — required disclosures for Rooibok HR System

The Privacy Policy must explicitly cover every data practice in this system. Key sections required under the Uganda Data Protection and Privacy Act 2019 (DPPA):

**1. Data controller identity**
Full legal name of the entity operating Rooibok HR System, physical address in Uganda, email address, phone number.

**2. What personal data is collected and from whom**

| Data category | Source | What is collected |
|--------------|--------|------------------|
| Company admin | Registration form | Full name, email, phone, company name, country, city, industry |
| Employee data | Added by Company Admin | Name, email, phone, department, designation, salary, attendance, payroll, leave, documents |
| CRM clients | Added by Company Admin | Name, email, phone, company, contact notes |
| Visitors | Added by Company Admin or kiosk | Name, phone, email, address, visit purpose, check-in/out times |
| Billing | Stripe, MTN, Airtel | Transaction reference, amount, payment method (no card numbers stored) |
| Technical | Automatic | IP address, browser type, session data, GPS coordinates (attendance clock-in only) |

**3. How data is used**

- Providing the HR management service
- Processing subscription payments
- Sending transactional notifications (payslips, leave approvals, subscription reminders)
- Marketing communications (only where explicit consent has been given)
- Legal compliance and record retention
- Improving and developing the platform (aggregate, anonymised analysis only)

**4. Data retention schedule — must match section 10.9:**

| Data type | Retention in live system | Archive retention | Deletion |
|-----------|------------------------|------------------|---------|
| Active employee HR records | Duration of subscription | 7 years post-termination | After 7-year archive period |
| Attendance records | 24 months | 7 years | After 7-year archive period |
| Payroll records | 36 months | 7 years | After 7-year archive period |
| Company admin contact details | Duration of account + 90 days | 7 years (anonymised after opt-out) | On verified deletion request |
| Marketing contacts | N/A — archive only | Until unsubscribed or deletion request | Immediately on unsubscribe + deletion request |
| Technical logs | 12 months | 3 years | Automatically |
| Sealed vault bundles | N/A — cold storage only | 7 years | After 7-year period or on verified request |

**5. Third-party data processors**

Must name every service that receives personal data:

| Processor | Purpose | Data shared | Their policy |
|-----------|---------|-------------|-------------|
| Stripe | Payment processing | Company name, email, transaction amount | stripe.com/privacy |
| MTN Uganda | Mobile money processing | Phone number, payment amount | mtn.co.ug/privacy |
| Airtel Uganda | Mobile money processing | Phone number, payment amount | airtel.co.ug/privacy |
| Africa's Talking | SMS delivery | Phone number, SMS content | africastalking.com/privacy |
| Backblaze B2 | Encrypted file storage | Encrypted archive bundles only | backblaze.com/privacy |
| Google Workspace (if used) | Email delivery | Sender/recipient email, email content | workspace.google.com/privacy |

**6. Data subject rights under DPPA 2019**

Every registered user has the right to:
- Request a copy of all personal data held about them (Data Access Request)
- Correct inaccurate data
- Request deletion of their personal data (subject to legal retention obligations)
- Withdraw marketing consent at any time
- Object to automated processing
- Lodge a complaint with the Personal Data Protection Office of Uganda

Contact to exercise rights: privacy@rooibok.co.ug

**7. Cross-border data transfers**

State explicitly that data may be processed by the third-party services listed above, some of which operate outside Uganda (Stripe — USA, Backblaze — USA, Africa's Talking — Kenya). These processors maintain their own GDPR/data protection compliance.

**8. Security measures**

- All data transmitted over HTTPS/TLS 1.3
- API keys and credentials stored encrypted at rest (AES-256)
- Passwords hashed with bcrypt (CI4 default)
- Two-factor authentication available for admin accounts
- Regular automated database backups
- Access controlled by role-based permissions — employees cannot access other employees' data

**9. Cookies** — refer to Cookie Policy page.

**10. Changes to this policy**

Date of last update displayed at top of page. Users notified by in-app announcement on material changes.

---

#### Cookie Policy — required disclosures

**Route:** `rooibok.co.ug/cookies`

The cookie policy covers two distinct contexts: the public landing page (pre-login) and the application (post-login).

**Cookie categories used by Rooibok HR System:**

| Cookie name | Category | Purpose | Expiry | Set by |
|------------|---------|---------|--------|--------|
| `ci_session` | Strictly necessary | Maintains logged-in session | Session (browser close) | Rooibok app |
| `csrf_cookie` | Strictly necessary | Prevents cross-site request forgery | Session | Rooibok app |
| `remember_lang` | Functional | Stores user's language preference | 1 year | Rooibok app |
| `consent_accepted` | Strictly necessary | Records cookie consent decision | 1 year | Rooibok app |
| `_stripe_mid` | Third-party (payment) | Stripe fraud prevention | 1 year | Stripe |
| `_stripe_sid` | Third-party (payment) | Stripe session management | 30 minutes | Stripe |

**What Rooibok HR System does NOT use:**
- Google Analytics or any analytics cookies
- Facebook Pixel or any social media tracking
- Advertising or retargeting cookies
- Any third-party tracking scripts on the application pages

**Cookie consent banner** — required on the public landing page (`/`, `/features`, `/pricing`, `/register`, `/demo`):

```html
<!-- app/Views/frontend/components/cookie_banner.php -->
<div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;
     background:var(--color-background-primary);border-top:1px solid var(--color-border-tertiary);
     padding:16px 24px;z-index:9999;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">

    <p style="flex:1;margin:0;font-size:13px;color:var(--color-text-secondary);">
        Rooibok HR System uses essential cookies to keep your session secure and remember your
        language preference. We do not use tracking or advertising cookies. By continuing to use
        this site you agree to our
        <a href="/cookies">Cookie Policy</a> and <a href="/privacy">Privacy Policy</a>.
    </p>

    <button onclick="acceptCookies()" class="btn btn-primary btn-sm">Accept</button>
    <a href="/cookies" style="font-size:12px;">Learn more</a>
</div>

<script>
function acceptCookies() {
    document.cookie = 'consent_accepted=1;max-age=31536000;path=/;SameSite=Lax;Secure';
    document.getElementById('cookie-banner').style.display = 'none';
}
// Show banner only if not yet accepted
if (!document.cookie.includes('consent_accepted=1')) {
    document.getElementById('cookie-banner').style.display = 'flex';
}
</script>
```

The banner is shown only on public-facing pages. Inside the logged-in application, the banner is never shown — users are already authenticated and the strictly necessary cookies are implicitly required for the service to function.

---

#### Terms of Service — key clauses for Rooibok HR System

**Route:** `rooibok.co.ug/terms`

The following clauses must be clearly stated — these are the ones that directly relate to the archive and marketing systems described in this upgrade plan:

**Data ownership clause:**
"All HR data entered into the system by a Company (employees, attendance records, payroll, documents) remains the exclusive property of that Company. Rooibok HR System acts as a data processor, not a data controller, for this data."

**Account closure and data retention clause:**
"Upon account closure or subscription cancellation, your company's data will be retained in our secure archive for a period of 7 (seven) years in accordance with Ugandan employment and tax law. During this period, your data is not accessible from the application but is maintained for legal compliance purposes. After the 7-year period, all data is permanently deleted. You may request a full data export in PDF, Excel, CSV, JSON, or Word format at any time during your active subscription by contacting support@rooibok.co.ug."

**Company admin contact retention for communications clause:**
"The name, email address, and phone number of the Company's designated administrator may be retained by Rooibok HR System after account closure for the purpose of legal and service communications (such as data deletion requests, legal notices, and compliance correspondence). This retention is based on legitimate interest under the DPPA 2019 and does not require separate consent."

**Marketing communications — separate consent clause:**
"Rooibok HR System may send product updates, new feature announcements, and re-engagement communications via email and SMS. Such marketing communications are sent only where you have provided explicit consent by selecting the relevant option during registration or in your account settings. You may withdraw this consent at any time by clicking the unsubscribe link in any marketing communication, by visiting your account settings, or by emailing privacy@rooibok.co.ug. Withdrawal of marketing consent does not affect your ability to use the service."

**Employee data clause:**
"By subscribing to Rooibok HR System, the Company agrees that it has obtained all necessary consents from its employees to process their personal data (including attendance, payroll, and leave records) using this platform. Rooibok HR System processes employee data solely on the instructions of the Company and does not use employee data for any other purpose."

**Vault bundle download clause:**
"Upon request, the Company may download a complete archive of all their data as a sealed, digitally verified bundle. This bundle contains the company's full HR records in multiple formats and is provided for the Company's own records and legal compliance purposes. The bundle is cryptographically verified with a SHA-256 checksum."

**Sub-processors clause:**
"Rooibok HR System uses the third-party processors listed in the Privacy Policy to deliver the service. By accepting these Terms, the Company acknowledges and consents to the use of these processors."

---

#### CMS fields for legal pages in Super Admin

All three legal pages are editable from Super Admin → Landing Page → Legal Pages tab. The CMS stores the content in `ci_landing_content` with sections:

| Section key | What it controls |
|-------------|----------------|
| `privacy_intro` | Opening paragraph of Privacy Policy |
| `privacy_controller` | Data controller contact details |
| `privacy_processors` | Third-party processors table (JSONB — add/remove rows) |
| `privacy_updated` | Date of last update (auto-updated on save) |
| `cookies_intro` | Opening paragraph of Cookie Policy |
| `cookies_table` | Cookie table (JSONB — add/remove rows) |
| `terms_intro` | Opening paragraph of Terms of Service |
| `terms_clauses` | Individual clauses (JSONB array — add/remove/reorder) |
| `legal_email` | Contact email for data requests (e.g. privacy@rooibok.co.ug) |
| `legal_address` | Physical address of the operator |
| `legal_company_name` | Full legal name of the operating entity |

The last-updated date is displayed at the top of each legal page and auto-set whenever the Super Admin saves changes. A version history is kept in the `ci_landing_content` table using a `version` integer column — old versions are not deleted, just superseded.

---

#### Add to Phase 8 audit checklist

- [ ] Privacy Policy page exists at `rooibok.co.ug/privacy` and is publicly accessible (no login required)
- [ ] Cookie Policy page exists at `rooibok.co.ug/cookies`
- [ ] Terms of Service page exists at `rooibok.co.ug/terms`
- [ ] All three pages are linked from the public footer and from the registration form
- [ ] Cookie consent banner appears on first visit to all public pages
- [ ] Banner does not appear on logged-in application pages
- [ ] `consent_accepted` cookie is set correctly on accept
- [ ] Privacy Policy names all third-party processors (Stripe, MTN, Airtel, Africa's Talking, Backblaze, Google)
- [ ] Data retention table in Privacy Policy matches actual retention periods in section 10.9
- [ ] Marketing consent checkbox is separate from ToS checkbox on registration form
- [ ] Terms of Service includes account closure and data retention clause
- [ ] Terms of Service includes marketing communications opt-in clause
- [ ] Vault bundle download clause is present in Terms of Service
- [ ] Super Admin can edit all legal page content from Landing Page CMS
- [ ] Last-updated date on legal pages reflects actual last save date
- [ ] Export buttons present on all archive portal data tables
- [ ] PDF export renders cleanly on A4 with Rooibok branding
- [ ] Excel export opens correctly in Microsoft Excel and Google Sheets
- [ ] CSV export has UTF-8 BOM — Ugandan names with special characters display correctly
- [ ] JSON export is valid JSON — validate with `json_decode($output, true) !== null`
- [ ] Word/DOCX export opens in Microsoft Word and LibreOffice
- [ ] ZIP export contains all five format files
- [ ] Exported data matches what is displayed in the filtered table view — no extra or missing rows
- [ ] Company Admin live report exports (attendance, payroll, leave) are scoped to their company only

#### Add to Phase 9 test matrix

| Test | Expected result |
|------|----------------|
| Export contact list filtered to Kampala, format = Excel | Downloaded .xlsx contains only Kampala contacts, all columns present, Rooibok teal header row |
| Export same list as CSV | UTF-8 BOM present, names with apostrophes or hyphens display correctly |
| Export same list as JSON | Valid JSON, `record_count` matches table row count |
| Export same list as PDF | A4 landscape, Rooibok branding, all rows visible |
| Export same list as Word | Table renders in Word with alternating row colours |
| Export same list as ZIP | ZIP contains 5 files: .pdf, .xlsx, .csv, .json, .docx — all valid |
| Company Admin exports their payroll report | Only their company's payroll appears — no other company data |
| Visit landing page without `consent_accepted` cookie | Cookie banner appears at bottom of page |
| Click Accept on cookie banner | Banner disappears, `consent_accepted=1` cookie set for 1 year |
| Revisit landing page | Cookie banner does not reappear |
| Visit `rooibok.co.ug/privacy` | Privacy Policy loads, shows last-updated date, names all processors |
| Visit `rooibok.co.ug/cookies` | Cookie Policy loads, cookie table is accurate |
| Visit `rooibok.co.ug/terms` | Terms of Service loads, all required clauses present |
| Super Admin edits privacy policy intro text and saves | Change visible on public page immediately, last-updated date refreshes |
| Register new company — check form | Two separate checkboxes: ToS agreement + marketing consent |
| Register with marketing consent unchecked | User saved with `marketing_consent = 0` — verify in DB |
| Register with marketing consent checked | User saved with `marketing_consent = 1` and `consent_date` timestamp |


### 10.12 — Archive Portal Audit Checks (add to Phase 8)

- [ ] Archive database `rooibok_archive` exists and is accessible from the app container
- [ ] `php spark archive:rotate` completes without errors on test data
- [ ] Attendance records older than 24 months are moved to `arc_attendance` and deleted from `ci_timesheet`
- [ ] Employee termination event triggers automatic archiving within 60 seconds (queue worker)
- [ ] Company cancellation triggers full snapshot creation in `arc_company_snapshots`
- [ ] Vault bundle ZIP generates correctly and contains all expected files
- [ ] SHA-256 checksum matches the downloaded bundle
- [ ] Bundle uploads to Backblaze B2 successfully
- [ ] Super Admin can view company archive list and download bundle
- [ ] Restore-to-live function re-creates company and all HR records correctly
- [ ] `arc_contacts` is populated with correct region, plan, and industry data
- [ ] Contact segmentation filters return accurate counts
- [ ] CSV export contains only consented contacts (`consent_given = 1`)
- [ ] Broadcast from archive portal sends only to consented, non-unsubscribed contacts
- [ ] Unsubscribe link works — sets `unsubscribed = 1` and shows confirmation page
- [ ] Staff and company admin users cannot access any `/erp/archive` URLs
- [ ] B2 credentials are stored encrypted in `ci_erp_settings`, not in `.env`

### 10.13 — Archive Tests (add to Phase 9)

| Test | Expected result |
|------|----------------|
| Manually set attendance record date to 25 months ago, run `archive:rotate` | Record appears in `arc_archive`, deleted from `ci_timesheet` |
| Terminate an employee | `arc_employees` row created within 60s, employee marked inactive in live DB |
| Cancel a company (manually trigger offboarding) | `arc_company_snapshots` row created, all HR data moved to archive tables, vault bundle queued |
| Download vault bundle | ZIP downloads, SHA-256 checksum matches value in `arc_company_snapshots` |
| Restore archived company | Company reappears in live system with all data intact, both live and archive records exist |
| Filter contacts by region "Kampala", consent = yes | Only Kampala companies with consent appear |
| Click unsubscribe link from marketing email | `unsubscribed = 1` set, confirmation page shown |
| Send broadcast to filtered contacts | Only consented, non-unsubscribed contacts receive message |
| Company admin attempts to access `erp/archive` | Redirected to their own dashboard — 403 |
| Storage dashboard | Shows accurate DB sizes and file counts for all three tiers |

---


*End of Rooibok HR System Master Upgrade Plan*  
*Document maintained by the development team. Update this file as each phase is completed.*
