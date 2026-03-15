# Rooibok HR System — Deep Development Plan
**Date:** March 15, 2026
**Status:** All phases (0-10) backend code complete. Frontend/UI needs major work.

---

## Current State

### What Works
- Docker stack: 8 containers running (PHP 8.2, PostgreSQL 16, Redis, Nginx, Beanstalkd, pgAdmin, Mailhog, Archive DB)
- Database: 107 tables seeded with demo data
- Landing page + Features + Pricing + Contact + Register pages (frontend)
- Login page (erp/login)
- Super Admin dashboard (basic metrics)
- REST API with JWT (8 endpoints + Swagger docs)
- Kiosk pages (attendance + visitor)

### What Needs Work
- Dashboards are minimal — need full redesign
- ~200+ controller methods need explicit routes (autoRoute disabled)
- Many sidebar links will 404
- Registration flow needs completion (what happens after sign up?)
- Subscription/payment flow not connected end-to-end
- Language system removed from frontend but still in ERP backend

---

## The User Flow (How Rooibok HR Actually Works)

### Flow 1: New Customer Acquisition
```
Visitor lands on rooibok.co.ug (landing page)
  → Browses Features, Pricing
  → Clicks "Get Started" or "Register"
  → Registration form: First Name, Last Name, Company Name, Email, Phone, Password
  → On submit: creates company user (user_type='company'), creates company_settings,
    creates company_membership with 30-day FREE trial, sends welcome email
  → Redirected to /erp/login with success message
  → Logs in → sees Company Admin Dashboard
  → Free trial active for 30 days
  → Before trial expires: prompted to choose a plan and pay
```

### Flow 2: Subscription Payment
```
Company Admin → Subscription/Billing page
  → Sees current plan, expiry date, status
  → Click "Upgrade" or "Renew"
  → Choose plan (Starter/Growth/Enterprise)
  → Choose billing mode:
    - Auto-renew (card only → Stripe subscription)
    - Manual (card/MTN MoMo/Airtel Money → one-time payment)
  → Payment processed
  → Subscription extended, invoice generated, confirmation email sent
```

### Flow 3: Company Admin Daily Use
```
Login → Company Dashboard showing:
  - Today's attendance (who's in, who's late, who's absent)
  - Pending leave requests
  - Upcoming payroll dates
  - Recent announcements
  - Employee count by department
  - Quick action buttons (Add Employee, Run Payroll, Approve Leave)

Sidebar modules:
  - HR: Employees, Departments, Designations, Shifts
  - Attendance: Daily attendance, Reports, Live board
  - Leave: Requests, Approvals, Calendar
  - Payroll: Run payroll, Payslips, Reports (with PAYE/NSSF)
  - Finance: Accounts, Deposits, Expenses, Transactions
  - Projects: Projects, Tasks, Kanban boards
  - Invoicing: Invoices, Estimates, Clients
  - Recruitment: Job postings, Candidates, Interviews
  - Training: Programs, Trainers
  - Documents: Employee docs, Company docs
  - Reports: Attendance, Payroll, Leave, Performance
  - Broadcasts: Send announcements/memos
  - Org Chart: Company hierarchy
  - Settings: Company settings, Roles, Templates
  - Subscription: Plan details, Invoices, Billing
```

### Flow 4: Staff (Employee) Daily Use
```
Login → Staff Dashboard showing:
  - Clock in/out button with geofencing
  - Today's attendance status
  - Leave balance
  - Recent payslips
  - Announcements from company admin

Sidebar modules (limited by role):
  - My Profile: Personal info, QR code, 2FA setup
  - Attendance: My timesheet, Clock in/out
  - Leave: Request leave, View balance, History
  - Payslips: View/download my payslips
  - Expenses: Submit expense claims
  - Documents: My documents
  - Announcements: View company announcements
```

### Flow 5: Super Admin (Platform Owner)
```
Login → Super Admin Dashboard showing:
  - Total companies, Active, Inactive, Expired
  - Total employees across all companies
  - Revenue (MRR, total collected)
  - Subscription expiry warnings
  - Recent registrations
  - System health (DB, Redis, Queue, Payments)
  - Quick actions (manage companies, settings, CMS, broadcasts)

Sidebar modules:
  - Dashboard
  - Companies: List all, View details, Activate/Deactivate, Extend subscription
  - Membership Plans: Create/Edit plans, Set pricing
  - Subscription Invoices: All payment history
  - Broadcasts: Send to all company admins
  - Landing Page CMS: Edit hero, features, pricing, FAQ, testimonials
  - Archive: Archived companies, Search records, Contacts, Vault
  - System Settings: General, Stripe, MTN, Airtel, SMS, Email, JWT, Tax
  - API Docs (link to Swagger)
```

---

## Development Phases (Next Steps)

### Phase A — Route Audit & Fix (Must do first)
**Goal:** Every sidebar link works. No 404s.

1. Scan all sidebar menu files for every `site_url()` link
2. Cross-reference with Routes.php
3. Add missing routes for ALL existing controller methods
4. Test every single link

### Phase B — Landing Page Redesign
**Goal:** Professional, modern landing page that converts visitors to sign-ups.

1. Hero section: headline, subtitle, CTA, hero image/illustration
2. Features section: 6-8 feature cards with icons
3. How it works: 3-step process (Register → Setup → Manage)
4. Pricing: 3 plans with comparison table
5. Testimonials: customer quotes
6. FAQ accordion
7. Footer: links, social, copyright
8. Mobile responsive
9. Remove all language-related UI
10. Rooibok branding throughout

### Phase C — Registration & Onboarding Flow
**Goal:** Visitor registers → gets free trial → guided setup.

1. Registration creates: user, company_settings, membership (30-day trial)
2. Welcome email sent via queue
3. First login: onboarding wizard
   - Step 1: Company info (address, logo)
   - Step 2: Add first department
   - Step 3: Add first employee
   - Step 4: Configure shift
4. Dashboard with "Getting Started" checklist

### Phase D — Super Admin Dashboard (Full SaaS Command Center)
**Goal:** Platform owner can manage everything from one place.

1. KPI cards: Companies, Revenue, Active/Expired, Employees
2. Revenue chart (monthly bar chart)
3. Subscription health: expiring, recently expired, recently registered
4. Company list with quick actions (view, extend, deactivate, archive)
5. Broadcast composer shortcut
6. System health indicators
7. Activity feed: recent registrations, payments, cancellations

### Phase E — Company Admin Dashboard (Modern HR Dashboard)
**Goal:** HR manager sees everything they need at a glance.

1. Attendance widget: live clock-in/out count, late arrivals
2. Leave widget: pending requests with approve/reject inline
3. Payroll widget: next payroll date, last payroll summary
4. Employee widget: headcount by department (donut chart)
5. Announcements: recent + create new inline
6. Quick actions: Add Employee, Run Payroll, Approve Leave, Clock In
7. Birthday/anniversary reminders
8. Upcoming holidays

### Phase F — Staff Dashboard (Employee Self-Service)
**Goal:** Employee can clock in, see payslips, request leave — all from dashboard.

1. Clock in/out button (large, centered, with GPS)
2. Today's status: clocked in at X, total hours
3. Leave balance: visual bars (annual, sick, etc.)
4. Recent payslips: last 3 with download PDF
5. My announcements
6. Expense claim shortcut

### Phase G — Subscription & Billing Page
**Goal:** Company admin can manage their subscription, pay, and view invoices.

1. Current plan card: name, price, expiry date, days remaining
2. Renew/Upgrade buttons
3. Plan comparison table
4. Payment method selection (Stripe/MTN/Airtel)
5. Billing history: list of all invoices with download
6. Cancel subscription option
7. Auto-renew toggle

### Phase H — Production Hardening
**Goal:** Everything production-ready.

1. Enable forceGlobalSecureRequests (with SSL)
2. Enable CSP with proper rules
3. Enable cookieSecure
4. Set APP_ENV=production
5. Run all tests
6. Performance testing with k6
7. DNS + SSL certificate setup
8. Final go-live checklist verification

---

## Priority Order
1. **Phase A** (Routes) — everything depends on this
2. **Phase C** (Registration) — needed for user acquisition
3. **Phase B** (Landing page) — first impression
4. **Phase E** (Company dashboard) — core product
5. **Phase F** (Staff dashboard) — daily use
6. **Phase D** (Super admin) — platform management
7. **Phase G** (Billing) — monetization
8. **Phase H** (Production) — go-live
