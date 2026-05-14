# BarcodeOS Setup Guide

This document describes the current setup for BarcodeOS after the Phase 0 completion, the strengthened Phase 1 foundation, and the Phase 3 closure-audited state.

BarcodeOS is a standalone Laravel SaaS application.

---

## 1. Current Stack

- Laravel 12
- PHP 8.4 in Docker
- Inertia.js + React
- Tailwind CSS
- Filament 5
- PostgreSQL
- Redis
- Docker + Docker Compose
- Coolify-compatible Dockerfile deployment

---

## 2. Local Development Mode

Local development uses the following Compose services:

- `app`
- `nginx`
- `db`
- `redis`
- `queue`
- optional `mailpit`

Default local URL:

```text
http://localhost:8000
```

Optional Mailpit UI:

```text
http://localhost:8025
```

---

## 3. First Boot Commands

From the project root:

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app composer install
docker compose exec app npm install
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app npm run build
```

If you want Mailpit too:

```bash
docker compose --profile mailpit up -d
```

---

## 4. Validation Commands

Validated in this foundation cycle:

```bash
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec app npm run build
docker compose exec app php artisan route:list
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

Note:

- Host-side `php artisan test` was not sufficient on this machine because the host PHP installation does not have the SQLite driver enabled.
- Container-based validation is the documented baseline.

---

## 5. Environment Structure

`.env.example` is organized around these sections:

- APP
- DATABASE
- REDIS
- QUEUE
- MAIL
- STORAGE
- BARCODE
- API
- PAYMENT PROVIDERS
- COOLIFY DEPLOY

Important rules:

- Do not commit real secrets
- Payment provider secrets stay in environment variables only
- Coolify token and webhook secret stay in environment variables only

---

## 6. Admin Access

Filament admin path:

```text
/admin
```

Create an admin user:

```bash
docker compose exec app php artisan make:filament-user
```

Role foundation uses Spatie Laravel Permission.

Current access rule:

- Local environment allows Filament access for convenience
- Non-local environments expect admin-role based access

Recommended production follow-up:

- Create the admin user
- Assign `super-admin` role through tinker or a later dedicated admin-role UI

---

## 7. Payment Provider Secrets

Payment provider records are seeded for:

- Stripe
- Paddle
- PayPal
- iyzico
- Manual bank transfer

Security rule:

- Secret credentials are not edited in Filament
- Admin only sees configured/not configured status
- Live values come from `.env` or Coolify environment variables

---

## 8. Safe System Update Flow

BarcodeOS includes an admin page for “Update from GitHub”.

Meaning of this feature:

- It only triggers a Coolify deploy webhook or API request
- It does not run `git pull`
- It does not run `shell_exec`, `exec` or `proc_open`
- If Coolify env settings are missing, the action is rejected safely
- Every result is written to `audit_logs` without storing secrets

Required env values for this feature:

```env
COOLIFY_ENABLED=true
COOLIFY_WEBHOOK_URL=
COOLIFY_WEBHOOK_SECRET=
COOLIFY_API_TOKEN=
COOLIFY_DEPLOY_BRANCH=main
COOLIFY_DEPLOY_METHOD=POST
COOLIFY_REQUEST_TIMEOUT=15
```

---

## 9. Production / Coolify Mode

Production deployment is documented as a different topology from local development.

Recommended Coolify model:

- Application container from `Dockerfile`
- Separate `queue` worker container from the same image
- Separate `scheduler` container or scheduled task from the same image
- Managed PostgreSQL service in Coolify
- Managed Redis service in Coolify

Why this differs from local:

- Local needs convenience and onboarding speed
- Production should avoid self-managed stateful services inside the same app deployment where possible

---

## 10. What This Setup Does Not Enable Yet

Still intentionally out of scope:

- Real payment checkout
- Barcode rendering engine
- Bulk generation workflow
- API barcode generation
- Full translation editor

The current setup is a safe SaaS foundation, not a full product implementation.

Current phase alignment:

- Phase 0: Completed
- Phase 1: Completed / strengthened
- Phase 2: Completed foundation
- Phase 3: Complete enough / closure audited
- Phase 4: Next / Rendering Engine

Phase 3 is complete enough to start Phase 4 rendering work.
