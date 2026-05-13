# BarcodeOS Architecture Decisions

This document records important product, technical and architecture decisions for BarcodeOS.

Every major decision should be documented here so future Codex tasks do not repeat old discussions or accidentally reverse important choices.

## Decision Format

Use this format for new decisions:

```text
## ADR-000X: Decision Title

Date:
Status:
Context:
Decision:
Reasoning:
Consequences:
Related files:
```

Decision statuses:
- Proposed
- Accepted
- Rejected
- Superseded

---

## ADR-0001: BarcodeOS is a Standalone SaaS Project

Date: 2026-05-13
Status: Accepted

### Context

The project is a professional online barcode generation platform with subscriptions, user accounts, usage limits, admin-managed barcode types, API access, billing and multi-language support.

A simple WordPress plugin/theme approach would make advanced SaaS features harder to maintain and scale.

### Decision

BarcodeOS will be built as a standalone SaaS application.

It will not be built on WordPress.

### Reasoning

A standalone SaaS structure gives better control over:
- Authentication
- Subscription logic
- Admin panel
- Feature entitlements
- Usage limits
- API access
- Queue-based bulk generation
- Payment integrations
- Multi-language architecture
- Security
- Deployment

### Consequences

The project requires a proper backend/frontend architecture.

WordPress shortcuts, plugin assumptions and theme-based design decisions should not be used.

---

## ADR-0002: Laravel Will Be the Backend Framework

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS needs a maintainable backend with strong support for authentication, queues, policies, database migrations, admin panel tooling and payment integrations.

### Decision

Laravel 12 will be used as the backend framework.

PHP version should be 8.3 or newer.

### Reasoning

Laravel is a strong fit because it provides:
- Mature authentication options
- Queue support
- Policies and gates
- Migrations and seeders
- Service container
- Eloquent ORM
- Scheduler
- Mail support
- Stripe/Cashier ecosystem
- Excellent compatibility with Filament
- Good compatibility with Docker and VPS hosting

### Consequences

Backend code should follow Laravel conventions.

Business logic should be placed in services, actions, jobs and policies rather than controllers.

---

## ADR-0003: Inertia.js + React Will Be Used for User-Facing UI

Date: 2026-05-13
Status: Accepted

### Context

The project needs a modern SaaS frontend without the overhead of maintaining a fully separate API-first SPA in the first version.

### Decision

Use Inertia.js + React for the user-facing application.

Use Tailwind CSS for styling.

### Reasoning

This approach gives:
- Modern React components
- Laravel routing and controller simplicity
- Less API boilerplate for the first version
- Strong dashboard UI possibilities
- Good fit for SaaS screens
- Easier development flow than a fully separate frontend/backend project

### Consequences

Frontend pages should be built as reusable React/Inertia components.

Backend controllers should return Inertia pages.

User-facing UI should remain premium, responsive and conversion-focused.

---

## ADR-0004: Filament Will Be Used for Admin Panel

Date: 2026-05-13
Status: Accepted

### Context

The project requires a powerful admin panel where nearly all business settings can be managed.

### Decision

Use Filament as the admin panel foundation.

### Reasoning

Filament provides:
- Fast admin resource creation
- Tables
- Forms
- Filters
- Actions
- Relation managers
- Dashboards
- Good Laravel integration
- Suitable UI for managing SaaS data models

### Consequences

Admin resources must not become bloated.

Business logic should remain in services/actions.

Filament should be used for management screens such as users, plans, features, usage limits, barcode types, payment providers, languages, translations, system settings and audit logs.

---

## ADR-0005: Business Rules Must Be Admin-Manageable

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS needs flexible subscription plans, limits, export permissions, barcode type access and feature availability.

Hard-coding these rules would make the product hard to operate commercially.

### Decision

Business rules must be database-driven and admin-manageable.

This includes:
- Plans
- Features
- Feature access
- Usage limits
- Export permissions
- Barcode type access
- Watermark settings
- Payment provider availability
- Language options
- System settings

### Reasoning

Admin-managed business rules allow:
- Creating new plans without developer involvement
- Changing prices and limits quickly
- Testing plan strategies
- Adding or removing features
- Supporting enterprise/custom packages
- Reducing future code changes

### Consequences

Do not write access logic based on plan names.

Use services like EntitlementService and UsageLimitService.

---

## ADR-0006: Use Feature Entitlements Instead of Plan Name Checks

Date: 2026-05-13
Status: Accepted

### Context

The product will have multiple plans: Free, Starter, Pro, Business and Enterprise. More plans may be added later.

If the code checks plan names directly, it will become fragile.

### Decision

Use a feature entitlement system.

Plan access must be checked through feature keys and limit keys, not hard-coded plan names.

### Reasoning

Feature entitlement checks allow flexible plan design.

Example feature keys:
- export.png
- export.svg
- export.pdf
- bulk.generate
- api.access
- watermark.remove
- team.members
- gs1.advanced

Example limit keys:
- daily_generation_limit
- monthly_generation_limit
- api_monthly_request_limit
- bulk_max_rows_per_job
- history_retention_days

### Consequences

Controllers, API endpoints and jobs must call entitlement and usage services.

Plan names should mainly be display/business objects, not hard-coded access controls.

---

## ADR-0007: Stripe First, Extensible Payment Provider Architecture

Date: 2026-05-13
Status: Accepted

### Context

The project needs paid subscriptions, invoices and billing. Stripe is a strong first provider, but other providers may be needed later.

### Decision

Stripe will be the first real payment provider.

The payment architecture must be extensible for:
- Paddle
- PayPal
- iyzico
- Manual bank transfer

### Reasoning

Stripe is globally mature and well-supported.

Future payment provider flexibility is important because the product may sell to different markets and may need local payment options.

### Consequences

Do not hard-code payment logic directly into plan or subscription models.

Use provider abstractions where practical.

Payment provider status/configuration metadata should be admin-manageable.

Secrets must remain in environment variables or secure secret storage.

---

## ADR-0008: English Is the Default Language, Multi-Language Support Required

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS is intended as a global product. The first site language should be English, but Turkish and other languages may be added.

### Decision

Default language is English.

The system must be designed for multi-language support from the beginning.

### Reasoning

Adding multi-language later is painful if text, plan labels, barcode type descriptions and email templates are hard-coded.

### Consequences

Use language and translation models.

User-facing labels should be translation-ready where practical.

Admin should eventually manage languages, translation keys, static page translations, FAQ translations, email template translations, plan translations, barcode type translations and SEO translations.

---

## ADR-0009: Barcode Types and Parameters Are Database-Driven

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS must support many barcode types and different configuration parameters.

Barcode types may need plan-specific access, validation rules, default values and export options.

### Decision

Barcode categories, barcode types and barcode parameters must be stored in the database and manageable from admin.

### Reasoning

This makes the system flexible and scalable.

Admin can later manage:
- Barcode type names
- Categories
- Example values
- Validation rules
- Supported export formats
- Default sizes
- Parameter definitions
- Documentation
- SEO metadata
- Visibility
- Plan availability

### Consequences

Do not assume barcode types are fixed forever.

Start with a limited set of barcode types, but design the architecture to expand.

---

## ADR-0010: Barcode Generation Should Be Service-Based

Date: 2026-05-13
Status: Accepted

### Context

Barcode generation involves validation, entitlement checks, usage limits, rendering, exporting, storing history and incrementing counters.

Putting this logic directly into controllers would make the system hard to maintain.

### Decision

Barcode generation must be handled through dedicated services.

Preferred services:
- App\Services\Barcode\BarcodeGenerationService
- App\Services\Barcode\BarcodeValidationService
- App\Services\Barcode\BarcodeRendererService
- App\Services\Barcode\BarcodeExportService
- App\Services\Barcode\BarcodeTypeRegistry

### Reasoning

Separate services keep the flow testable and maintainable.

### Consequences

Controllers should orchestrate requests, not contain business rules.

Jobs and API endpoints should reuse the same services.

---

## ADR-0011: Usage Counters Must Be Reliable and Period-Based

Date: 2026-05-13
Status: Accepted

### Context

Plans will have daily, monthly, API and bulk limits.

Usage must be tracked reliably to prevent abuse and enforce plan rules.

### Decision

Usage counters should track feature usage by user, plan, feature key, source and period.

Suggested fields:
- user_id
- plan_id
- feature_key
- period_type
- period_start
- period_end
- used
- limit
- source

### Reasoning

This supports flexible usage reporting and reliable limit checks.

### Consequences

Usage must be checked before successful operations.

Usage should be incremented only after successful generation/export.

Failed validation should not consume usage.

---

## ADR-0012: Bulk Generation Must Be Queue-Based

Date: 2026-05-13
Status: Accepted

### Context

Bulk barcode generation can involve many rows and file exports. Running this synchronously can cause timeouts and bad user experience.

### Decision

Bulk barcode generation must be processed through queues.

### Reasoning

Queue-based processing supports:
- Long-running jobs
- Progress tracking
- Retry handling
- Error reports
- ZIP/PDF export generation
- Better user experience

### Consequences

Redis and queue workers are part of the architecture.

Bulk generation should not be implemented before the core barcode engine is stable.

---

## ADR-0013: API Access Is a Paid Entitlement

Date: 2026-05-13
Status: Accepted

### Context

API access has infrastructure cost and commercial value.

### Decision

API access must be feature-gated.

Only plans with the api.access entitlement should use API generation endpoints.

### Reasoning

This supports monetization and abuse control.

### Consequences

API keys must be stored securely.

API request limits must be enforced.

API usage must be logged.

Frontend API lock states are only UX; backend must enforce access.

---

## ADR-0014: Docker and Coolify-Compatible Deployment

Date: 2026-05-13
Status: Accepted

### Context

The project should be deployable on a VPS and compatible with containerized hosting workflows.

### Decision

Use Docker + Docker Compose for development and production readiness.

Keep deployment compatible with Coolify or standard VPS Docker workflows.

### Reasoning

Docker supports consistent environments and easier deployment.

### Consequences

The project should include:
- Dockerfile
- docker-compose.yml
- Redis service
- Database service
- Queue worker guidance
- Scheduler guidance
- Environment documentation

---

## ADR-0015: Do Not Build Everything in One Codex Task

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS is a large SaaS project. Asking Codex to build everything in one task will likely create unstable, low-quality code.

### Decision

Build the project phase by phase.

### Reasoning

Incremental development gives:
- Better code quality
- Easier debugging
- Safer architecture
- Less accidental rewrites
- Clear acceptance criteria

### Consequences

Every Codex task should identify the current phase and implement a small, safe part of the roadmap.

---

## ADR-0016: Premium SaaS UI Is Required, But Backend Correctness Comes First

Date: 2026-05-13
Status: Accepted

### Context

The product needs to look professional, but the core commercial value depends on correct plans, limits, billing, barcode generation and admin control.

### Decision

The UI should be premium and polished, but backend architecture and entitlement correctness come first.

### Reasoning

A beautiful static UI without reliable subscription and usage logic is not a real SaaS product.

### Consequences

Frontend work should use real data wherever possible.

Avoid fake disconnected screens.

Design system and skills should guide UI quality while implementation remains backend-driven.

---

## ADR-0017: PostgreSQL Is the Default Database Choice

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS needs reliable JSON support, clear indexing options and a production-friendly database for SaaS reporting, usage counters and configuration-heavy models.

### Decision

PostgreSQL is the default database choice for local and production-ready setup.

### Reasoning

PostgreSQL is a strong fit for:

- JSON/JSONB-heavy SaaS metadata
- Future reporting and query flexibility
- Stable Laravel support
- Good Docker and Coolify compatibility

### Consequences

The local Docker stack and `.env.example` use PostgreSQL by default.

MySQL remains technically possible later, but PostgreSQL is the documented baseline.

---

## ADR-0018: Laravel 12 Foundation Uses the Official React Starter Kit

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS needed Laravel 12, auth foundation, Inertia.js, React and Tailwind CSS without introducing unnecessary package drift in the first setup task.

### Decision

Use the official `laravel/react-starter-kit` Laravel 12-compatible starter foundation.

### Reasoning

This gives:

- First-party Laravel alignment
- Inertia.js + React already wired
- Authentication foundation already wired
- Tailwind and Vite foundation already wired

### Consequences

User-facing work builds on the starter structure instead of custom bootstrapping.

---

## ADR-0019: Filament 5 Is the Admin Foundation for Laravel 12

Date: 2026-05-13
Status: Accepted

### Context

The admin panel needed the most current stable Filament line that is compatible with Laravel 12.

### Decision

Use Filament 5 as the admin panel foundation.

### Reasoning

Filament 5 is current, stable and aligned with the Laravel 12 setup selected for BarcodeOS.

### Consequences

Admin resources and pages are implemented against Filament 5 APIs.

This decision was validated before implementation and recorded because the task explicitly required version-safety.

---

## ADR-0020: Use Spatie Laravel Permission for Admin Role Foundation

Date: 2026-05-13
Status: Accepted

### Context

Phase 1 requires role and permission groundwork for Filament access and future admin authorization.

### Decision

Use `spatie/laravel-permission` for role and permission foundation.

### Reasoning

It is a mature Laravel-standard choice for:

- Admin role assignment
- Filament access gating
- Later permission-based admin actions

### Consequences

Permission tables are the intentional extra schema beyond the explicit domain table list.

Role UI remains a later task, but the data foundation is now present.

---

## ADR-0021: Secrets Are Environment-Only, Not Admin-Stored

Date: 2026-05-13
Status: Accepted

### Context

BarcodeOS needs payment provider credentials, Coolify deploy credentials and webhook secrets without exposing them in database-backed admin fields.

### Decision

Payment provider secrets, Coolify token and webhook secret values must be read only from `.env` or hosting environment variables.

### Reasoning

This reduces secret exposure risk and keeps sensitive values out of admin-editable plain database records.

### Consequences

- `payment_providers.secret_config` is not used for live secret storage
- Payment provider admin screens only show configured/not configured state
- Coolify secrets are never written to database audit metadata

---

## ADR-0022: In-App Update Only Triggers Coolify Deployment

Date: 2026-05-13
Status: Accepted

### Context

The project requires an admin-visible “Update from GitHub” capability, but live containers must not run unsafe local git or shell execution flows.

### Decision

The in-app update action only triggers a Coolify deploy webhook or API call.

It never performs:

- `git pull`
- `shell_exec`
- `exec`
- `proc_open`
- or similar local command execution

### Reasoning

Deployment orchestration belongs to the platform layer, not the live application container.

### Consequences

- Missing Coolify env configuration causes safe rejection
- Every attempt is audit-logged without storing secrets
- The admin UI documents this safety rule directly

---

## ADR-0023: Local And Production Container Topologies Are Different By Design

Date: 2026-05-13
Status: Accepted

### Context

Local development needs a complete stack in one compose file, while production on Coolify should prefer managed infrastructure for stateful services.

### Decision

Use two documented modes:

- Local development: `app`, `nginx`, `db`, `redis`, `queue`, optional `mailpit`
- Production/Coolify: `app` + `queue`/`scheduler` containers with managed PostgreSQL and Redis services

### Reasoning

This keeps local onboarding practical while keeping production safer and easier to operate.

### Consequences

Docker and setup docs explicitly describe both modes.

---

## Future Decisions To Add

The following decisions are not final yet and should be documented later:

- Exact barcode generation libraries
- Stripe Cashier vs custom Stripe integration
- Storage strategy for generated barcode files
- Public guest generation allowed or disabled
- Watermark behavior for Free plan
- File retention periods
- API rate limit strategy
- Multi-tenant/team structure depth
- Admin theme customization strategy
- Mail provider
