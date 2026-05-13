# BarcodeOS Architecture Skill

Use this skill when working on BarcodeOS architecture, backend structure, database design, service design, SaaS foundations, admin-managed configuration, plans, features, entitlements, usage limits, billing foundations, API access, bulk generation, or deployment readiness.

BarcodeOS is a standalone Laravel SaaS project. It is not WordPress.

---

## 1. Core Identity

BarcodeOS is a professional barcode generation SaaS platform.

It must support:

- Users
- Admin panel
- Subscription plans
- Feature entitlements
- Usage limits
- Barcode generation
- Barcode history
- Export formats
- Bulk generation
- API access
- Multi-language support
- Payment provider readiness
- Reports and audit logs

Default language: English.

Multi-language support must be prepared from the beginning.

---

## 2. Main Architecture Rule

Everything that affects business behavior must be admin-manageable.

Do not hard-code:

- Plan rules
- Feature access
- Barcode type access
- Export permissions
- Daily limits
- Monthly limits
- API limits
- Bulk limits
- Watermark settings
- Retention settings
- Payment provider availability
- Language settings

Use database-driven configuration.

---

## 3. Technology Stack

Use this stack unless the project explicitly changes direction:

- Laravel 12
- PHP 8.3+
- Inertia.js
- React
- Tailwind CSS
- Filament Admin Panel
- PostgreSQL preferred, MySQL acceptable
- Redis
- Docker
- Docker Compose
- Stripe-first billing foundation
- S3-compatible storage for production

---

## 4. Preferred Laravel Structure

Use a clean Laravel structure.

Recommended directories:

```text
app/
  Actions/
  Services/
  Models/
  Policies/
  Jobs/
  Filament/
  Http/
  Support/
```

Domain-specific structure:

```text
app/Services/
  Barcode/
  Billing/
  Entitlements/
  Payments/
  Plans/
  Translation/
  Usage/

app/Actions/
  Barcode/
  Billing/
  Bulk/
  Subscriptions/
  Usage/

app/Jobs/
  Barcode/
  Bulk/
  Billing/
```

Controllers should be thin.

Business logic belongs in:

- Services
- Actions
- Jobs
- Policies
- Events/listeners when useful

---

## 5. Do Not Build Everything at Once

BarcodeOS must be built incrementally.

Recommended order:

1. Project foundation
2. Admin foundation
3. Plans, features and usage limits
4. Barcode type and parameter management
5. Barcode generation engine
6. User dashboard and history
7. Billing foundation
8. Stripe integration
9. Bulk generation
10. API access
11. Multi-language management
12. Reports and production readiness

Never attempt the full SaaS in one task.

---

## 6. Feature Entitlement Rules

Use feature keys and limits instead of plan-name checks.

Bad:

```php
if ($user->plan === 'pro') {
    return true;
}
```

Good:

```php
$entitlementService->allows($user, 'export.pdf');
```

Important services:

```text
App\Services\Entitlements\EntitlementService
App\Services\Usage\UsageLimitService
App\Services\Plans\PlanResolverService
```

Feature examples:

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

Limit examples:

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

---

## 7. Plan and Feature Data Model Guidance

Core models:

```text
Plan
Feature
PlanFeature
Subscription
UsageCounter
```

Plans should support:

- name
- slug
- description
- monthly_price
- yearly_price
- currency
- trial_days
- is_public
- is_active
- sort_order
- metadata

Features should support:

- key
- name
- description
- category
- value_type
- is_active
- sort_order

Plan features should support:

- plan_id
- feature_id
- enabled
- value
- limit_value
- metadata

Usage counters should support:

- user_id
- plan_id
- feature_key
- period_type
- period_start
- period_end
- used
- limit
- source

---

## 8. Barcode Configuration Data Model Guidance

Core models:

```text
BarcodeCategory
BarcodeType
BarcodeParameter
GeneratedBarcode
BarcodeExport
```

Barcode categories should be admin-managed.

Initial categories:

```text
Linear / 1D
Retail
2D
GS1
Postal & Logistics
Payment & Banking
```

Initial barcode types:

```text
QR Code
Code 128
Code 39
EAN-13
UPC-A
Data Matrix
PDF417
```

Barcode types should support:

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
- parameter_schema
- documentation
- seo_title
- seo_description
- sort_order

Barcode parameters should support:

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

---

## 9. Barcode Generation Architecture

Do not place barcode generation logic directly in controllers.

Use services:

```text
App\Services\Barcode\BarcodeGenerationService
App\Services\Barcode\BarcodeValidationService
App\Services\Barcode\BarcodeRendererService
App\Services\Barcode\BarcodeExportService
App\Services\Barcode\BarcodeTypeRegistry
```

Recommended generation flow:

1. Resolve user.
2. Resolve active plan.
3. Check feature entitlement.
4. Check usage limit.
5. Resolve barcode type.
6. Validate input data.
7. Validate dynamic parameters.
8. Render barcode.
9. Store generated barcode history if allowed.
10. Store or prepare export file.
11. Increment usage.
12. Return response.

Failed validation must not consume usage.

Successful generation should increment usage.

---

## 10. Usage Limit Architecture

Usage must be reliable and backend-enforced.

Usage sources:

```text
web
api
bulk
admin
```

Period types:

```text
daily
monthly
yearly
lifetime
```

Rules:

- Check usage before the operation.
- Increment only after success.
- Do not trust frontend usage displays.
- Track enough metadata for reports.
- Avoid race conditions when possible.

---

## 11. Billing Architecture

Stripe is the first real payment provider.

Future providers:

```text
Paddle
PayPal
iyzico
Manual bank transfer
```

Do not implement real payment flows before billing foundations are stable.

Payment provider settings should be admin-manageable.

Secrets must not be committed or exposed.

Suggested models:

```text
PaymentProvider
Subscription
Payment
Invoice
Coupon
WebhookLog
```

Suggested services:

```text
App\Services\Billing\BillingService
App\Services\Billing\SubscriptionService
App\Services\Payments\PaymentProviderManager
App\Services\Payments\StripePaymentProvider
```

---

## 12. Multi-Language Architecture

Default language: English.

Prepare for Turkish and other languages.

Suggested models:

```text
Language
Translation
```

Translation areas:

- UI labels
- Static pages
- FAQ
- Plan names/descriptions
- Barcode type names/descriptions
- Email templates
- SEO metadata

Avoid scattering hard-coded user-facing labels where translation keys are clearly needed.

---

## 13. API Architecture

API access is a paid entitlement.

API must support:

- API keys
- Hashed key storage
- API request logs
- Rate limits
- Usage limits
- Barcode generation endpoint
- API documentation

Backend must enforce:

- api.access
- usage limits
- rate limits
- user ownership
- valid barcode type access

---

## 14. Bulk Architecture

Bulk generation is a premium feature.

It must be queue-based.

Bulk flow:

1. Upload CSV/Excel.
2. Select barcode type.
3. Map columns.
4. Validate rows.
5. Create bulk job.
6. Process queue.
7. Generate outputs.
8. Package results.
9. Save errors.
10. Notify user.

Do not build bulk generation before the barcode engine is stable.

---

## 15. Security Rules

Always enforce security backend-side.

Use:

- Policies
- Gates
- Role-based permissions
- CSRF protection
- Validation
- Rate limiting
- Signed URLs where useful
- Secure file download routes
- Hashed API keys
- Audit logs

Do not:

- Expose raw storage paths
- Store payment secrets in plain visible fields
- Log sensitive payment details
- Trust frontend-only locks
- Skip authorization checks
- Weaken security for convenience

---

## 16. Admin Panel Rules

Use Filament.

Admin panel should manage:

- Users
- Plans
- Features
- Usage limits
- Subscriptions
- Barcode categories
- Barcode types
- Barcode parameters
- Payment providers
- Languages
- Translations
- System settings
- Audit logs
- Reports

Keep Filament resources clean.

Move complex logic to services/actions.

Use filters, badges, search and clear status indicators.

---

## 17. Testing Guidance

Add tests for important business rules.

Priority tests:

- EntitlementService
- UsageLimitService
- PlanResolverService
- BarcodeValidationService
- BarcodeGenerationService
- API authorization
- File download authorization

Example tests:

- Free user cannot export PDF.
- Pro user can export PDF.
- Daily limit blocks generation.
- Failed validation does not increment usage.
- Successful generation increments usage.
- User cannot access another user's generated barcode.
- API access requires api.access.

---

## 18. Documentation Rules

Keep documentation updated.

Important docs:

```text
AGENTS.md
docs/ROADMAP.md
docs/PHASES.md
docs/DECISIONS.md
docs/ARCHITECTURE.md
docs/SETUP.md
docs/FEATURES.md
```

If architecture changes, update:

```text
docs/DECISIONS.md
docs/ARCHITECTURE.md
```

If phase status changes, update:

```text
docs/PHASES.md
docs/ROADMAP.md
```

---

## 19. Task Behavior

When working on BarcodeOS:

1. Inspect existing project structure.
2. Read AGENTS.md.
3. Read docs/PHASES.md.
4. Identify current phase.
5. Make the smallest safe change.
6. Avoid unrelated rewrites.
7. Keep everything admin-manageable.
8. Use services for business logic.
9. Enforce backend authorization.
10. Update docs when needed.
11. Explain changed files and commands to run.

---

## 20. Strong Warnings

Do not:

- Convert the project to WordPress.
- Hard-code plan behavior.
- Hard-code payment provider logic.
- Build all barcode types in one pass.
- Put barcode generation in a controller.
- Add fake billing that looks real.
- Build disconnected static UI.
- Expose unsafe file URLs.
- Skip backend entitlement checks.
- Implement full SaaS in a single task.
