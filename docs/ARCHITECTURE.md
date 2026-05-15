# BarcodeOS Architecture

This document describes the planned technical architecture for BarcodeOS.

BarcodeOS is a standalone, subscription-based, admin-manageable barcode generation SaaS platform.

Default language: English.
Secondary language support must be prepared from the beginning.

---

## 1. Product Architecture Summary

BarcodeOS has four major application areas:

1. Public website
2. User application
3. Admin panel
4. API layer

### Public Website

The public website includes:

- Landing page
- Homepage-based barcode generator landing foundation
- Barcode type listing pages
- Pricing page
- API documentation page
- Help / FAQ pages
- Login and registration pages

Current implementation note:

- the homepage is the canonical public discovery surface in Phase 3
- public `Barcode Generator` and `API` navigation links currently use homepage anchors rather than separate public routes
- the public generator landing panel is intentionally read-only and non-rendering
- Phase 4.1 adds only authenticated QR Code in-memory SVG preview
- the public generator landing panel still does not render, download or export anything
- broader rendering, downloads and export previews remain later Phase 4 work

### User Application

The user application includes:

- Dashboard
- Barcode generator
- Generated barcode history
- Saved templates
- Bulk generation
- API access
- Billing
- Invoices
- Account settings
- Team management, later phase

### Admin Panel

The admin panel includes:

- Dashboard
- Users
- Plans
- Features
- Usage limits
- Subscriptions
- Barcode categories
- Barcode types
- Barcode parameters
- Generated barcodes
- Bulk jobs
- API management
- Payment providers
- Payments
- Invoices
- Coupons
- Languages
- Translations
- System settings
- Audit logs
- Reports

### API Layer

The API layer includes:

- API key authentication
- Barcode generation endpoint
- API usage tracking
- Rate limiting
- API request logs
- API documentation support

---

## 2. Recommended Technology Stack

### Backend

- Laravel 12
- PHP 8.3+
- Laravel queues
- Laravel scheduler
- Laravel policies and gates
- Laravel validation
- Laravel events/listeners where useful

### Frontend

- Inertia.js
- React
- Tailwind CSS
- Reusable component structure
- Translation-ready labels

### Admin

- Filament Admin Panel

### Database

Preferred:

- PostgreSQL

Acceptable:

- MySQL

The database choice should be documented in docs/DECISIONS.md when final.

### Cache and Queue

- Redis

Redis should be used for:

- Queue jobs
- Cache
- Rate limiting support
- Bulk generation jobs
- API usage protection

### Storage

Development:

- Local storage

Production:

- S3-compatible storage
- DigitalOcean Spaces or compatible object storage

Generated barcode files must not expose raw storage paths.

### Payment

First provider:

- Stripe

Future providers:

- Paddle
- PayPal
- iyzico
- Manual bank transfer

### Deployment

- Docker
- Docker Compose
- Coolify-compatible
- VPS-compatible

Current implementation note:

- Local development uses `app`, `nginx`, `db`, `redis`, `queue` and optional `mailpit`
- Production/Coolify is documented as `app` + `queue`/`scheduler` containers with managed PostgreSQL and Redis services

---

## 3. High-Level Request Flow

### User Barcode Generation Flow

1. User opens generator page.
2. User selects barcode type.
3. System loads barcode parameters from database.
4. User enters data and parameters.
5. User submits generation request.
6. Backend resolves current user and plan.
7. Backend checks feature entitlement.
8. Backend checks usage limit.
9. Backend validates barcode data.
10. Backend validates barcode parameters.
11. Phase 4.1 renders only QR Code preview as in-memory SVG.
12. The response returns preview data only for supported QR requests.
13. No persistence occurs in Phase 4.1.
14. No file storage or download response occurs in Phase 4.1.
15. No usage increment occurs in Phase 4.1.

### API Barcode Generation Flow

1. API request is sent with API key.
2. API key is validated.
3. User and plan are resolved.
4. api.access entitlement is checked.
5. API usage limit is checked.
6. Barcode type and parameters are validated.
7. Barcode is generated.
8. Request log is saved.
9. Usage counter is incremented.
10. API response is returned.

### Bulk Generation Flow

1. User uploads CSV or Excel file.
2. User selects barcode type.
3. User maps file columns to barcode fields.
4. Backend validates file and rows.
5. Backend creates bulk job.
6. Queue worker processes rows.
7. Barcode files are generated.
8. ZIP/PDF/export package is created.
9. Bulk job status is updated.
10. User downloads result.

---

## 4. Main Laravel Directory Strategy

Recommended structure:

```text
app/
  Actions/
    Barcode/
    Billing/
    Bulk/
    Subscriptions/
    Usage/

  Services/
    Barcode/
    Billing/
    Entitlements/
    Payments/
    Plans/
    Translation/
    Usage/

  Models/

  Policies/

  Jobs/
    Barcode/
    Bulk/
    Billing/

  Filament/
    Resources/
    Pages/
    Widgets/

  Http/
    Controllers/
      Public/
      User/
      Api/
    Requests/

  Support/
    Enums/
    ValueObjects/
```

### Important Rule

Controllers should stay thin.

Business logic belongs in:

- Services
- Actions
- Jobs
- Policies
- Events/listeners when useful

---

## 5. Core Domain Modules

## 5.1 Users and Roles

Purpose:

- Register users
- Authenticate users
- Manage admin access
- Manage user permissions
- Support future team features

Likely models:

- User
- Role
- Permission

Possible package:

- spatie/laravel-permission

Final package decision should be documented in docs/DECISIONS.md.

---

## 5.2 Plans and Features

Purpose:

- Manage subscription packages
- Manage feature access
- Manage limits
- Avoid hard-coded plan logic

Likely models:

- Plan
- Feature
- PlanFeature
- Subscription
- UsageCounter

Core services:

- App\Services\Entitlements\EntitlementService
- App\Services\Plans\PlanResolverService
- App\Services\Usage\UsageLimitService
- App\Services\Barcode\BarcodeAccessService

Example feature keys:

```text
barcode.generate
barcode.history
barcode.template.save
export.png
export.svg
export.pdf
export.eps
bulk.generate
api.access
api.keys.manage
team.members
watermark.remove
gs1.advanced
billing.invoices
```

Example limit keys:

```text
daily_generation_limit
monthly_generation_limit
api_monthly_request_limit
bulk_monthly_job_limit
bulk_max_rows_per_job
history_retention_days
file_retention_days
team_member_limit
```

### Entitlement Rule

Never check access like:

```text
if user plan is Pro, allow PDF
```

Always check access like:

```text
Does this user have export.pdf entitlement?
```

---

## 5.3 Barcode Configuration

Purpose:

- Make barcode categories admin-manageable
- Make barcode types admin-manageable
- Make barcode parameters admin-manageable
- Support future barcode types without redesigning database

Likely models:

- BarcodeCategory
- BarcodeType
- BarcodeParameter

Initial categories:

- Linear / 1D
- Retail
- 2D
- GS1
- Postal & Logistics
- Payment & Banking

Initial barcode types:

- QR Code
- Code 128
- Code 39
- EAN-13
- UPC-A
- Data Matrix
- PDF417

Barcode types should include fields such as:

- name
- slug
- category_id
- description
- status
- icon
- example_value
- validation_rules
- default_width
- default_height
- default_margin
- default_format
- supported_export_formats
- required_features
- parameter_schema
- documentation
- seo_title
- seo_description
- sort_order

Architecture direction:

- barcode types are database-driven records, not separate Laravel modules
- barcode behavior should expand through shared services plus category-based renderer and validator strategies
- GS1-specific parsing now plugs into the validation layer through `App\Services\Barcode\Gs1Parser` for explicitly signaled barcode types only
- `gs1-datamatrix` is seeded as a dedicated GS1 barcode type, while normal `data-matrix` remains separate
- Phase 3 uses `App\Services\Barcode\ParameterSchemaResolver` to turn raw barcode type schema and active parameter records into a shared generator-form contract

Barcode parameters should include fields such as:

- barcode_type_id
- label
- key
- type
- default_value
- min_value
- max_value
- options
- help_text
- is_required
- available_features
- sort_order
- is_active
- metadata

---

## 5.4 Barcode Generation Engine

Purpose:

- Validate barcode input
- Validate dynamic parameters
- Render barcode
- Export barcode
- Store history
- Enforce entitlements and usage limits

Core services:

- App\Services\Barcode\BarcodeGenerationService
- App\Services\Barcode\BarcodeValidationService
- App\Services\Barcode\BarcodeRendererService
- App\Services\Barcode\BarcodeExportService
- App\Services\Barcode\BarcodeTypeRegistry

Likely models:

- GeneratedBarcode
- BarcodeExport

### Generation Responsibilities

BarcodeGenerationService:

- Coordinates the overall generation flow.
- Calls entitlement and usage services.
- Calls validation service.
- Calls renderer/export service.
- Stores history.
- Increments usage.

BarcodeValidationService:

- Validates barcode input.
- Validates barcode type rules.
- Validates dynamic parameters.
- Runs before rendering only.
- Consumes resolved schema from `App\Services\Barcode\ParameterSchemaResolver`.
- May delegate GS1 DataMatrix parsing to `App\Services\Barcode\Gs1Parser` only for explicitly signaled barcode types.
- Ignores unknown parameters safely during normalization.
- Does not render or export output.
- Does not store barcode history or files.
- Does not create `usage_counters`.
- Does not create `generated_barcodes` or `barcode_exports`.
- Does not increment usage.

BarcodeGenerationService:

- Lives in `App\Services\Barcode\BarcodeGenerationService`.
- Acts as a Phase 4.1 coordinator with a narrow first renderer slice.
- Calls `BarcodeValidationService::validateGenerationRequest(...)`.
- Preserves the normalized validation payload in a stable generation result contract.
- Checks `BarcodeTypeRegistry` for renderer availability after validation passes.
- Returns `validation_failed` when validation fails.
- Returns `renderer_not_supported` for non-QR barcode types in Phase 4.1.
- Produces QR Code in-memory SVG preview only.
- Does not export output.
- Does not store barcode history or files.
- Does not create `usage_counters`.
- Does not create `generated_barcodes` or `barcode_exports`.
- Does not increment usage.
- Does not expose download behavior.

Gs1Parser:

- Lives in `App\Services\Barcode\Gs1Parser`.
- Is a service-layer parser only.
- Cleans BOM and invisible leading characters from raw input.
- Normalizes literal `\F` sequences into the ASCII GS separator.
- Supports parenthesized AI input for `01`, `21`, `91`, `92` and `93`.
- Distinguishes normal Data Matrix from GS1 DataMatrix without rendering.
- Supports only these GS1 DataMatrix structures in this phase:
  - `01 + GTIN(14) + 21 + serial + 93 + value`
  - `01 + GTIN(14) + 21 + serial + 91 + value + 92 + value`
- Produces normalized encode data and human-readable AI text for later rendering work.
- Does not call entitlement, usage, billing, controller, renderer, export or persistence flows.

BarcodeRendererService:

- Converts validated data into barcode output.
- Wraps underlying barcode libraries.

BarcodeExportService:

- Handles PNG, SVG, PDF, EPS or ZIP export logic.
- Stores generated files safely.
- Returns secure download references.

BarcodeTypeRegistry:

- Maps database barcode type records to supported renderer implementations.
- Allows expansion over time.
- Should favor shared service orchestration with category-based renderer and validator resolution rather than separate Laravel modules per barcode type.
- May later expose a GS1 parser integration point for GS1-family validation, without requiring the parser to exist yet.

Current foundation note:

- `BarcodeAccessService` may be used before rendering to validate barcode-type and export-format entitlement access
- it does not render, export, store files or increment usage
- `BarcodeValidationService` is also pre-render only and intentionally side-effect-free
- `BarcodeGenerationService` now begins Phase 4.1 with QR Code in-memory SVG preview only
- non-QR barcode types must still return `renderer_not_supported`
- it must not create records, create files, expose downloads or increment usage
- authenticated app flows may read barcode type config through `/app/barcodes/types/{barcodeType:slug}/config`
- that endpoint is read-only and returns resolved parameter schema, access info and export availability only
- `Gs1Parser` is intentionally separate from entitlement, usage, billing and rendering so GS1 parsing remains deterministic and testable
- GS1 DataMatrix rendering and export generation are still future work even though parser and seeded metadata now exist

---

## 5.5 Usage Limits

Purpose:

- Enforce free and paid plan limits
- Track usage by feature, source and period
- Support reports and abuse prevention

Likely model:

- UsageCounter

Suggested fields:

- id
- user_id
- plan_id
- feature_key
- period_type
- period_start
- period_end
- used
- limit
- source
- created_at
- updated_at

Period types:

- daily
- monthly
- yearly
- lifetime

Sources:

- web
- api
- bulk
- admin

Usage rules:

- Check limits before successful operation.
- Increment only after successful operation.
- Failed validation should not consume usage.
- Backend must enforce limits.
- Frontend usage cards are only informational.

---

## 5.6 Billing and Payments

Purpose:

- Support subscription plans
- Support payment provider setup
- Support invoices
- Support provider extensibility

Likely models:

- PaymentProvider
- Subscription
- Payment
- Invoice
- Coupon
- WebhookLog, later phase

Payment providers:

- Stripe
- Paddle
- PayPal
- iyzico
- Manual bank transfer

Payment provider metadata should be admin-manageable.

Secrets should not be stored as plain visible admin fields.

### Billing Services

Suggested services:

- App\Services\Billing\BillingService
- App\Services\Billing\SubscriptionService
- App\Services\Payments\PaymentProviderManager
- App\Services\Payments\StripePaymentProvider

Stripe should be implemented after the billing foundation is stable.

---

## 5.7 Multi-Language System

Purpose:

- English default
- Turkish and other languages later
- Admin-managed translations

Likely models:

- Language
- Translation

Future related models:

- Page
- FaqItem
- EmailTemplate

Translation areas:

- UI labels
- Static pages
- FAQ
- Plan names/descriptions
- Barcode type names/descriptions
- Email templates
- SEO metadata

Rules:

- English is default.
- Do not block early development by translating everything on day one.
- Prepare structure early.
- Avoid scattering hard-coded labels that clearly belong to translations.

---

## 5.8 API System

Purpose:

- Let paid users generate barcodes programmatically
- Enforce API entitlements and limits
- Support developer adoption

Likely models:

- ApiKey
- ApiUsageLog

API features:

- API key creation
- API key regeneration
- Hashed API key storage
- API request logs
- API rate limits
- API usage dashboard
- Barcode generation endpoint

Security rules:

- Store API keys securely.
- Never show full API key after creation unless explicitly designed as one-time display.
- Enforce backend entitlement checks.
- Enforce backend rate limits.

---

## 5.9 Bulk Generation

Purpose:

- Let paid users generate many barcodes from CSV/Excel
- Process jobs safely
- Avoid request timeouts

Likely models:

- BulkJob
- BulkJobRow, later if needed

Bulk services/jobs:

- BulkImportValidationService
- BulkGenerationService
- ProcessBulkBarcodeJob
- CreateBulkExportArchiveJob

Flow:

1. Upload file
2. Map columns
3. Validate rows
4. Create job
5. Process queue
6. Generate outputs
7. Create downloadable package
8. Save errors
9. Notify user

---

## 5.10 Audit Logs and Reports

Purpose:

- Track important admin and system actions
- Provide operational visibility
- Help debugging and security review

Likely models:

- AuditLog
- Report models are optional; reports may be query-based initially.

Audit events:

- Admin login
- Plan changes
- Feature changes
- Payment provider changes
- User suspension
- Usage reset
- Barcode type changes
- System setting changes

Reports:

- Total users
- Active subscriptions
- Monthly recurring revenue
- Barcode generations
- API requests
- Bulk jobs
- Failed payments
- Top barcode types
- Export format usage
- Free-to-paid conversion

---

## 6. Database Table Plan

Likely tables:

```text
users
roles
permissions
plans
features
plan_features
subscriptions
usage_counters
barcode_categories
barcode_types
barcode_parameters
generated_barcodes
barcode_exports
bulk_jobs
api_keys
api_usage_logs
payment_providers
payments
invoices
coupons
languages
translations
system_settings
audit_logs
```

Additional tables may be added when needed, but every new table should have a clear purpose.

---

## 7. Admin Panel Architecture

Use Filament resources.

Initial admin resources:

- PlanResource
- FeatureResource
- BarcodeCategoryResource
- BarcodeTypeResource
- BarcodeParameterResource
- SystemSettingResource
- LanguageResource
- TranslationResource
- PaymentProviderResource
- UserResource

Later admin resources:

- SubscriptionResource
- UsageCounterResource
- GeneratedBarcodeResource
- BulkJobResource
- ApiKeyResource
- PaymentResource
- InvoiceResource
- CouponResource
- AuditLogResource
- Report pages/widgets

Admin rules:

- Keep resources readable.
- Move complex logic into services/actions.
- Use filters and search.
- Add useful table columns.
- Add clear badges for status fields.
- Avoid fake actions that do not work.

---

## 8. Frontend Architecture

User-facing frontend should use:

- React pages
- Reusable components
- Inertia responses
- Tailwind CSS

Suggested frontend structure:

```text
resources/js/
  Components/
    App/
    Barcode/
    Billing/
    Dashboard/
    Forms/
    Layout/
    Pricing/
    Usage/

  Layouts/
    PublicLayout.jsx
    AppLayout.jsx
    AuthLayout.jsx

  Pages/
    Public/
    Auth/
    Dashboard/
    Barcode/
    Billing/
    Api/
    Bulk/
    Settings/
  features/
    barcode/
      components/
      hooks/
      pages/
      services/
      types/
```

Frontend barcode direction:

- future barcode UI work should follow a responsive feature-based structure under `resources/js/features/barcode/`
- initial responsive barcode foundation components now live under that feature folder
- Phase 3 now connects those components to an authenticated Inertia generator page
- the generator page uses the live config endpoint client-side and exposes a config-driven dynamic parameter form
- validate-only user flow is active through a dedicated authenticated endpoint
- Phase 4.1 may show QR Code in-memory SVG preview only
- the generator UI must not offer persistence, file storage or download behavior in Phase 4.1
- non-QR barcode types should continue surfacing `renderer_not_supported`
- the public homepage now exposes a separate non-rendering discovery panel that uses only controller-provided database data
- the public homepage does not call config or validate endpoints
- export, history persistence, downloads and non-QR rendering remain future work
- do not split barcode UI into a separate `mobile` folder
- responsive behavior should be handled within shared feature components and layouts

Important components:

- PlanBadge
- UsageProgress
- UpgradePrompt
- BarcodePreview
- BarcodeTypeSelector
- BarcodeParameterForm
- ExportFormatSelector
- EmptyState
- LoadingState
- ErrorState
- PricingCard
- FeatureComparisonTable

Frontend rules:

- Use real backend data where possible.
- Keep UI premium and clean.
- Keep strings translation-ready where practical.
- Do not rely on frontend-only permission checks.
- Backend must enforce access.

---

## 9. Security Architecture

Required security principles:

- Use policies for model access.
- Use gates/permissions for admin actions.
- Use CSRF protection.
- Use validation requests.
- Use signed URLs for secure downloads where useful.
- Do not expose raw storage paths.
- Hash API keys.
- Do not log sensitive payment data.
- Do not expose secrets in admin UI.
- Enforce entitlements backend-side.
- Enforce usage limits backend-side.

Important policies:

- GeneratedBarcodePolicy
- BarcodeTemplatePolicy
- ApiKeyPolicy
- BulkJobPolicy
- SubscriptionPolicy
- Admin policies for management resources

---

## 10. File Storage Architecture

Generated barcode files may include:

- PNG
- SVG
- PDF
- EPS
- ZIP

Storage rules:

- Local disk in development.
- S3-compatible disk in production.
- Store metadata in database.
- Use secure download endpoints.
- Do not expose raw storage URLs unless intentionally signed and temporary.
- File retention should be admin-configurable.

Generated barcode history and files should respect plan rules.

---

## 11. Queue and Scheduler Architecture

Queues should handle:

- Bulk generation
- Export package creation
- Email sending
- Payment webhook processing if needed
- Cleanup jobs
- File retention cleanup
- Usage report aggregation if needed

Scheduler should handle:

- Expired file cleanup
- Expired history cleanup
- Subscription status checks if needed
- Usage counter maintenance if needed
- Report aggregation if added

Redis should be available from the beginning.

---

## 11.1 Deployment Control Safety

BarcodeOS includes a deployment control foundation through `App\Services\Deployment\CoolifyDeployService`.

Rules:

- The application may trigger a Coolify deploy webhook or API call
- The application must not run `git pull`, `shell_exec`, `exec`, `proc_open` or similar local shell execution inside the live container
- Missing Coolify env configuration must cause safe rejection
- All results must be written to `audit_logs` without storing secrets

---

## 12. Testing Strategy

Important test areas:

- EntitlementService
- UsageLimitService
- PlanResolverService
- BarcodeValidationService
- BarcodeGenerationService
- API authorization
- File download authorization
- Payment webhook handling, later
- Admin permissions

Initial test examples:

- Free user can generate when under daily limit.
- Free user cannot generate after daily limit.
- User without export.pdf cannot export PDF.
- Failed validation does not increment usage.
- Successful generation increments usage.
- User cannot access another user's generated barcode.
- API access requires api.access entitlement.

---

## 13. Development Phase Dependency

Do not implement later phases before foundations are ready.

Recommended dependency order:

```text
Phase 0: Project Foundation
Phase 1: SaaS Admin Foundation
Phase 2: Plans, Features and Usage Limits
Phase 3: Barcode Type and Parameter Management
Phase 4: Barcode Generation Engine
Phase 5: User Dashboard and History
Phase 6: Pricing and Billing Foundation
Phase 7: Stripe Subscription Integration
Phase 8: Bulk Barcode Generation
Phase 9: API Access
Phase 10: Multi-Language Management
Phase 11: Reports, Audit Logs and Polish
Phase 12: Production Deployment Readiness
```

---

## 14. First Implementation Target

The first Codex implementation should create:

- Laravel project foundation
- Inertia React
- Tailwind
- Filament
- Docker
- Redis
- Authentication foundation
- Initial migrations/models for:
  - plans
  - features
  - plan_features
  - usage_counters
  - barcode_categories
  - barcode_types
  - barcode_parameters
  - generated_barcodes
  - languages
  - translations
  - system_settings
  - payment_providers
  - audit_logs
- Seeders for:
  - default plans
  - default features
  - default barcode categories
  - initial barcode types
  - default languages
- Initial services:
  - EntitlementService
  - UsageLimitService
  - PlanResolverService
- Basic Filament resources for core management

Do not implement real Stripe flow or full barcode generation in the first implementation.

---

## 15. Architecture Guardrails

Always follow these rules:

- No WordPress.
- No hard-coded plan access.
- No hard-coded payment provider logic.
- No barcode logic inside controllers.
- No fake billing flow shown as real.
- No raw storage path exposure.
- No frontend-only security.
- No large unrelated rewrites.
- No static UI disconnected from backend when real data is available.
- No full SaaS implementation in one task.
- Keep documentation updated.
