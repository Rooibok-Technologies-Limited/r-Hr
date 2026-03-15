# Rooibok HR System — Master Development Plan v2
**Date:** March 15, 2026
**Updated:** March 15, 2026
**Status:** Backend phases 0-10 complete. Frontend/UX + deployment model redesign in progress.

---

## Architecture Decision: SaaS vs Desktop vs Hybrid

### Recommendation: **Progressive Web App (PWA) + Offline-First**

| Option | Pros | Cons | Verdict |
|--------|------|------|---------|
| Pure SaaS (web only) | Easy to deploy, auto-updates, one codebase | No offline, needs constant internet | Not ideal for Uganda (connectivity gaps) |
| Desktop app (Electron) | Full offline, hardware access | Hard to update, per-machine install, 2 codebases | Too heavy for HR system |
| **PWA + Service Worker** | **Offline attendance caching, installable on desktop/mobile, auto-sync when online, one codebase, works like desktop app** | Limited hardware access (solved by USB/Bluetooth APIs) | **Best fit** |

**Plan:** Keep the web app but add:
- Service Worker for offline caching (attendance, payslips, employee list)
- IndexedDB for offline attendance queue (syncs when online)
- `manifest.json` for "Install as App" on Chrome/Edge
- Background Sync API to push cached clock-ins when connectivity returns
- The attendance kiosk already works offline-first by design (camera + local QR processing)

### Custom Domain Support
Each company gets their HR portal at a subdomain or custom domain:
- Default: `companyname.rooibok.co.ug`
- Custom: `hr.theircompany.com` (CNAME to rooibok.co.ug)
- Implementation: Wildcard SSL cert + nginx server_name matching + company lookup by domain
- Stored in `ci_erp_company_settings.custom_domain` column

---

## Deployment Models

### Model A: Hosted SaaS (Primary — rooibok.co.ug)
- We host everything, companies subscribe monthly
- No installation needed — register on website, start using
- Data isolated per company_id in shared database
- This is the default model

### Model B: On-Premise / Self-Hosted (License Key)
- Client downloads the system, installs on their own server
- **License key system:**
  - Super admin generates a license key from a license server
  - Key encodes: company name, max employees, expiry date, features enabled
  - Installer validates the key via API call to `license.rooibok.co.ug/validate`
  - If valid: installation proceeds. If expired: system enters read-only mode
  - Keys are SHA-256 signed JWTs: `base64(header).base64(payload).signature`
  - Offline fallback: key contains expiry date, system checks locally
- **License key generation (Super Admin portal):**
  - License management page in super admin dashboard
  - Generate key: select plan, max employees, duration, client name
  - Key is emailed to client or displayed for copy
  - License server tracks active installations

### Model C: White-Label
- Same as Model B but with client's branding
- Logo, colors, company name — all from database (already implemented via CMS)
- Custom domain support
- Sold at premium tier

---

## Landing Page — Fully Dynamic (CMS-Driven)

### Principle: Zero hardcoded content
Every text, image, button, color, and section on the landing page is stored in `ci_landing_content` and editable from Super Admin → Landing Page CMS.

### Sections (all from database)

| Section | Editable Fields | Storage |
|---------|----------------|---------|
| **Hero** | Headline, subtitle, CTA button text, CTA URL, background image, overlay color | `ci_landing_content` section='hero' |
| **Features** | Up to 12 cards: icon, title, description. Section heading. | JSONB in `ci_landing_content` |
| **How It Works** | 3-4 steps: number, title, description, icon | JSONB |
| **Pricing** | Auto-pulled from `ci_membership` table — plans, prices, features per plan | Database-driven |
| **Testimonials** | Up to 8: name, company, role, quote, photo | JSONB |
| **Stats** | 4 figures: number, label (e.g. "500+ Companies") | JSONB |
| **FAQ** | Up to 15 Q&A pairs, collapsible accordion | JSONB |
| **Clients/Partners** | Logo carousel: up to 12 client logos | JSONB |
| **CTA Banner** | Headline, subtitle, button text, button URL | `ci_landing_content` |
| **Contact** | Address, phone, email, WhatsApp, Google Maps embed URL | `ci_landing_content` |
| **Footer** | Copyright text, social links (Facebook, Twitter, LinkedIn, Instagram), legal page links | `ci_landing_content` |
| **SEO** | Page title, meta description, OG image, OG title | `ci_landing_content` |
| **Navigation** | Logo, menu items (auto-generated from sections), Login/Register button text | `ci_landing_content` |
| **Colors** | Primary color, secondary color, accent color, header bg | `ci_landing_content` |

### Sub-Pages (also CMS-driven)
- `/features` — detailed feature descriptions with screenshots
- `/pricing` — plan comparison with feature matrix
- `/contact` — contact form + map + info
- `/register` — registration form (fields configurable)
- `/privacy`, `/cookies`, `/terms` — legal pages (rich text editor)
- `/demo` — auto-login to demo account

### Super Admin CMS Interface
- Visual editor with live preview
- Drag-and-drop section reordering
- Image upload with cropping
- Color picker for theme colors
- Mobile preview toggle
- "Publish" button with version history

---

## Registration & Onboarding Flow

### Step 1: Registration (public /register page)
```
Fields:
- First Name *
- Last Name *
- Company Name *
- Work Email *
- Phone Number *
- Country (dropdown, default Uganda)
- Password *
- Marketing consent checkbox
- Terms of Service checkbox

On Submit:
1. Validate all fields
2. Create ci_erp_users (user_type='company', is_active=1)
3. Create ci_erp_company_settings (defaults: UGX, Africa/Kampala)
4. Create ci_company_membership (30-day free trial, expiry = today + 30)
5. Create ci_erp_users_role (Administrator role with full permissions)
6. Send welcome email (queued via Beanstalkd)
7. Send welcome SMS if phone provided
8. Redirect to /erp/login with "Registration successful! Please log in."
```

### Step 2: First Login — Onboarding Wizard
```
Shown only once (flag: company_settings.onboarding_complete = 0)

Step 1/5: Company Profile
  - Upload company logo
  - Address, city, country
  - Industry type
  - Company size (1-10, 11-50, 51-200, 200+)

Step 2/5: Create Departments
  - Quick-add 3-5 departments
  - Suggestions: HR, Finance, Operations, IT, Sales

Step 3/5: Set Up Shifts
  - Define working hours (default 8am-5pm Mon-Fri)
  - Set holidays (pre-loaded Uganda holidays)

Step 4/5: Add Your First Employee
  - Quick form: name, email, department, designation
  - Or "Skip — I'll do this later"

Step 5/5: Choose Your Plan
  - Show 3 plans with prices
  - "Start Free Trial" (default)
  - Or "Pay Now" to skip trial

→ Mark onboarding_complete = 1
→ Redirect to Company Dashboard
```

### Step 3: Trial Expiry → Payment
```
During trial (30 days):
- Banner at top: "X days left in your free trial. Upgrade now."
- Day 7: Email reminder
- Day 3: SMS + Email
- Day 1: Urgent banner + modal
- Day 0: Account locked (read-only), redirect to subscription page
- After payment: instant restore, full access
```

---

## Notification System (Email + SMS + In-App)

### Transactional Notifications (automatic)

| Event | Email | SMS | In-App |
|-------|-------|-----|--------|
| New registration | Welcome email with login link | Welcome SMS | - |
| Trial expiring (7/3/1 days) | Yes | Day 3 + Day 1 | Banner |
| Subscription expired | Yes | Yes | Modal |
| Payment successful | Invoice attached | Confirmation | Bell |
| Payment failed | Retry instructions | Alert | Banner |
| Employee added | Credentials email to employee | - | Bell |
| Leave approved/rejected | Yes | Yes | Bell |
| Payslip generated | PDF attached | "Payslip ready" | Bell |
| Attendance anomaly (geofence) | To admin | - | Bell |
| Password reset | Reset link | - | - |
| 2FA setup | Backup codes | - | - |

### Broadcast Notifications (manual — from admin)
- Company Admin → their employees (memos, announcements)
- Super Admin → all company admins (system updates, pricing changes)
- Supports personalisation tokens ({{first_name}}, {{company_name}}, etc.)
- Queue-based delivery via Beanstalkd
- Delivery reports per recipient

### Demo/Marketing Notifications
- New visitor signs up for demo → tracked in `ci_landing_content` analytics
- Demo session completed → follow-up email after 24 hours: "Ready to get started?"
- Abandoned registration (started but didn't complete) → reminder email after 1 hour
- Post-trial churned companies → re-engagement email series (Day 7, 14, 30 after expiry)

---

## Attendance Hardware Support

### Option 1: QR Code (Already Built)
- Cost: UGX 200K-400K (Android tablet + stand)
- Flow: Employee scans QR from phone/badge → kiosk page records attendance
- Offline: Service Worker caches, syncs when online

### Option 2: ZKTeco Biometric (Already Built — webhook)
- Cost: UGX 450K-1.2M per device
- Flow: Fingerprint/face → device pushes to `/api/v1/webhooks/zkteco`
- Models: ZKTeco K40 (fingerprint), F18 (fingerprint+RFID), MB10 (face)

### Option 3: RFID Card Reader
- Cost: UGX 80K-150K per reader
- Flow: Employee taps RFID card → reader sends card ID to API
- Integration: Same webhook as ZKTeco or direct USB to kiosk tablet

### Option 4: NFC Phone Tap (New — Plan)
- Employee taps phone on NFC-enabled tablet
- Uses Web NFC API (Chrome Android only)
- Kiosk page reads NFC tag containing employee_id
- Falls back to QR if NFC unavailable

### Option 5: GPS-Only Mobile Clock-In (New — Plan)
- Employee opens PWA on their phone
- Clicks "Clock In" → captures GPS
- Geofence validates location
- No hardware needed — phone is the device
- Best for field workers, remote staff

### Offline Attendance Flow
```
Employee clocks in on kiosk (no internet):
1. Service Worker intercepts API call
2. Stores in IndexedDB: {employee_id, timestamp, lat, lng, type: 'clock_in'}
3. Shows "Clocked In (offline)" with amber indicator
4. Background Sync API triggers when connectivity returns
5. Service Worker replays queued clock-ins to REST API
6. Success: remove from IndexedDB, show green indicator
7. Failure: retry on next sync
```

---

## License Key System (For Self-Hosted Deployments)

### Key Format
```
RBHR-XXXX-XXXX-XXXX-XXXX
```

### Key Contains (JWT payload)
```json
{
  "iss": "rooibok.co.ug",
  "sub": "Acme Corporation",
  "plan": "enterprise",
  "max_employees": 500,
  "features": ["payroll", "attendance", "recruitment", "finance"],
  "issued_at": "2026-03-15",
  "expires_at": "2027-03-15",
  "sig": "sha256_signature"
}
```

### Validation Flow
```
Installation:
1. Installer asks for license key
2. If internet available: validates against license.rooibok.co.ug/api/validate
3. If no internet: decodes JWT locally, checks signature with public key, checks expiry
4. Valid: proceed with installation
5. Invalid/expired: show error, block installation

Runtime (daily check):
1. Cron job checks key expiry
2. If expired: system enters read-only mode
3. If < 30 days remaining: show warning banner
4. If internet available: phone home for updates
```

### License Generation (Super Admin)
```
Super Admin → License Management
  → Generate New Key
  → Select: client name, plan, max employees, duration (1yr/2yr/lifetime)
  → Key generated, displayed, and logged
  → Email to client with installation instructions
```

---

## Custom Domain Support

### Database
```sql
ALTER TABLE ci_erp_company_settings ADD COLUMN custom_domain VARCHAR(200);
ALTER TABLE ci_erp_company_settings ADD COLUMN subdomain VARCHAR(50);
```

### How It Works
1. Company signs up → gets `companyname.rooibok.co.ug` automatically
2. Company can set custom domain in Settings → Domain
3. Instructions shown: "Add a CNAME record pointing to rooibok.co.ug"
4. System validates DNS with `checkdnsrr()`
5. Nginx wildcard server_name catches all domains
6. PHP middleware looks up company by `HTTP_HOST` → sets company context
7. SSL via Let's Encrypt with wildcard cert or per-domain cert

### Nginx Config
```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name *.rooibok.co.ug;  # Wildcard subdomain
    # ... existing config
}

server {
    listen 80;
    listen 443 ssl;
    server_name ~^(?<custom_domain>.+)$;  # Custom domains
    # Same config — PHP resolves company from Host header
}
```

---

## Development Phases (Updated Priority Order)

### Phase A — Route Audit & Fix (Days 1-2)
Every sidebar link and form action must have an explicit route.
- Scan all menu files for URLs
- Cross-reference with Routes.php
- Add all missing routes
- Test every link

### Phase B — Dynamic Landing Page (Days 3-5)
- Rewrite all frontend views to pull from `ci_landing_content`
- Build Super Admin CMS editor with live preview
- Remove all hardcoded text from frontend
- Responsive design with theme colors from DB

### Phase C — Registration & Onboarding (Days 6-8)
- Complete registration flow with trial creation
- Build 5-step onboarding wizard
- Welcome email/SMS templates
- First-login detection and redirect

### Phase D — Company Admin Dashboard (Days 9-12)
- Attendance widget, leave widget, payroll widget
- Employee stats, department breakdown
- Quick actions, announcements
- Birthday/holiday reminders

### Phase E — Staff Dashboard (Days 13-14)
- Clock in/out with GPS
- Leave balance, recent payslips
- Announcements, expense shortcuts

### Phase F — Super Admin Dashboard (Days 15-17)
- KPI metrics, revenue charts
- Company management table
- Subscription health monitoring
- System health, activity feed
- License key management (if self-hosted model included)

### Phase G — Subscription & Billing (Days 18-20)
- Current plan display, upgrade/renew flow
- Payment integration end-to-end (Stripe/MTN/Airtel)
- Invoice history with PDF download
- Auto-renew toggle, cancellation

### Phase H — PWA & Offline Support (Days 21-23)
- Service Worker with offline caching strategy
- manifest.json for installability
- IndexedDB attendance queue
- Background Sync for offline clock-ins
- Offline indicator UI

### Phase I — Notification Engine (Days 24-26)
- Email templates for all transactional events
- SMS templates (under 160 chars)
- In-app notification bell with real-time count
- Demo follow-up automation
- Re-engagement email series

### Phase J — Custom Domain & License System (Days 27-30)
- Subdomain auto-generation on registration
- Custom domain configuration UI
- DNS validation
- License key generation and validation
- Self-hosted installer with key check

### Phase K — Production Launch (Days 31-35)
- SSL certificate (Let's Encrypt)
- Enable all security settings
- Performance testing (k6)
- Content population (landing page, plans, emails)
- DNS cutover to rooibok.co.ug
- Monitoring setup (UptimeRobot)
- Launch announcement

---

## Database Additions Needed

```sql
-- Registration & onboarding
ALTER TABLE ci_erp_company_settings ADD COLUMN onboarding_complete SMALLINT DEFAULT 0;
ALTER TABLE ci_erp_company_settings ADD COLUMN custom_domain VARCHAR(200);
ALTER TABLE ci_erp_company_settings ADD COLUMN subdomain VARCHAR(50);
ALTER TABLE ci_erp_company_settings ADD COLUMN industry VARCHAR(100);
ALTER TABLE ci_erp_company_settings ADD COLUMN company_size VARCHAR(20);

-- License keys (self-hosted)
CREATE TABLE ci_license_keys (
    key_id SERIAL PRIMARY KEY,
    license_key TEXT NOT NULL,
    client_name VARCHAR(200) NOT NULL,
    client_email VARCHAR(200),
    plan VARCHAR(50) NOT NULL,
    max_employees INTEGER DEFAULT 50,
    features TEXT,
    issued_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at DATE NOT NULL,
    is_active SMALLINT DEFAULT 1,
    last_validated TIMESTAMP WITH TIME ZONE,
    installation_count INTEGER DEFAULT 0
);

-- Demo/marketing tracking
CREATE TABLE ci_demo_sessions (
    session_id SERIAL PRIMARY KEY,
    visitor_email VARCHAR(200),
    visitor_name VARCHAR(200),
    started_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    ended_at TIMESTAMP WITH TIME ZONE,
    pages_visited INTEGER DEFAULT 0,
    follow_up_sent SMALLINT DEFAULT 0
);

-- Abandoned registrations
CREATE TABLE ci_abandoned_registrations (
    abandon_id SERIAL PRIMARY KEY,
    email VARCHAR(200),
    first_name VARCHAR(100),
    company_name VARCHAR(200),
    step_reached INTEGER DEFAULT 1,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    reminder_sent SMALLINT DEFAULT 0
);
```

---

## Estimated Timeline

| Phase | Days | Cumulative |
|-------|------|-----------|
| A: Routes | 2 | 2 |
| B: Landing CMS | 3 | 5 |
| C: Registration | 3 | 8 |
| D: Company Dashboard | 4 | 12 |
| E: Staff Dashboard | 2 | 14 |
| F: Super Admin Dashboard | 3 | 17 |
| G: Billing | 3 | 20 |
| H: PWA/Offline | 3 | 23 |
| I: Notifications | 3 | 26 |
| J: Domain/License | 4 | 30 |
| K: Production Launch | 5 | 35 |

**Total: ~35 working days to production-ready launch**

---

*This plan is the source of truth. Update as decisions are made.*
