# Rooibok HR System — Go-Live Checklist

## Infrastructure

- [ ] Production VPS provisioned (min 4 vCPU, 8GB RAM, 100GB SSD)
- [ ] Docker stack running
- [ ] SSL certificate + auto-renewal
- [ ] Daily backup cron
- [ ] Backups uploaded to off-server storage
- [ ] Server monitoring configured

## Application

- [ ] CI_ENVIRONMENT = production in .env
- [ ] All Stripe keys are live keys
- [ ] MTN MoMo in production mode
- [ ] Airtel Money in production mode
- [ ] SMS provider using production credentials
- [ ] SMTP configured with real email
- [ ] Super Admin 2FA set up

## Content

- [ ] Landing page content accurate
- [ ] Membership plans with correct UGX pricing
- [ ] PAYE bands match current URA rates
- [ ] NSSF rates correct
- [ ] Demo company has realistic data
- [ ] Email templates updated with Rooibok branding

## Final Verification

- [ ] Phase 8 audit fully passed
- [ ] Phase 9 tests all passing
- [ ] End-to-end walkthrough completed
