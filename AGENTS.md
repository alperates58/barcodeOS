
# AGENTS.md

# BarcodeOS - Project Instructions for Codex Agents

## Project Identity

BarcodeOS is a standalone professional SaaS platform for generating, managing, exporting and integrating barcodes.

This is not a WordPress project.

The product is a global B2B SaaS platform where users can create 1D, 2D, QR, GS1, retail, logistics, payment and API-based barcode workflows.

The default product language is English. The application must be designed from the beginning with multi-language support.

The project must be built as a production-ready SaaS application, not as a demo, mockup or static website.

## Recommended Tech Stack

Use the following stack unless there is a strong technical reason to change it:

- Backend: Laravel 12
- PHP: 8.3+
- Frontend: Inertia.js + React
- Styling: Tailwind CSS
- Admin Panel: Filament
- Database: PostgreSQL preferred, MySQL acceptable
- Queue / Cache: Redis
- Storage: Local for development, S3-compatible storage for production
- Billing foundation: Stripe-first, but payment provider system must be extensible
- Deployment: Docker + Docker Compose, Coolify-compatible

## Core Product Requirements

BarcodeOS must support:

- User registration and login
- User dashboard
- Admin panel
- Subscription plans
- Feature entitlement system
- Daily, monthly and API usage limits
- Barcode generation
- Barcode history
- Export formats
- Bulk barcode generation
- API access
- Payment provider configuration
- Invoices and billing records
- Multi-language content
- Admin-managed translations
- Admin-managed barcode types
- Admin-managed barcode parameters
- Admin-managed plan limits
- Admin-managed system settings
- Admin audit logs
- Reports and usage analytics

## Critical Rule: Everything Must Be Admin-Manageable

Do not hard-code business rules that should be controlled from the admin panel.

The following must be database-driven and manageable from the admin panel:

- Plans
- Plan prices
- Monthly prices
- Yearly prices
- Trial days
- Currencies
- Features
- Plan feature access
- Daily generation limits
- Monthly generation limits
- API request limits
- Bulk generation limits
- Export format permissions
- Barcode type permissions
- Watermark settings
- History retention rules
- File retention rules
- Team member limits
- Payment providers
- Payment provider status
- Tax/VAT settings
- Invoice settings
- Languages
- Translation keys
- Static page content
- FAQ content
- Email templates
- Notification templates
- Barcode categories
- Barcode types
- Barcode parameters
- Barcode validation rules
- Barcode default values
- Barcode export options
- API access rules
- System settings

Bad example:

    if ($user->plan === 'pro') {
        $canExportPdf = true;
    }

Good example:

    $canExportPdf = $entitlementService->allows($user, 'export.pdf');

## Feature Entitlement System

BarcodeOS must use a feature entitlement system.

Plan access must be checked through feature keys and limits, not by hard-coded plan names.

Example feature keys:

- barcode.generate
- barcode.history
- barcode.template.save
- export.png
- export.svg
- export.pdf
- export.eps
- bulk.generate
- api.access
- api.keys.manage
- team.members
- watermark.remove
- gs1.advanced
- billing.invoices

Example limit keys:

- daily_generation_limit
- monthly_generation_limit
- api_monthly_request_limit
- bulk_monthly_job_limit
- bulk_max_rows_per_job
- history_retention_days
- file_retention_days
- team_member_limit

All feature and limit checks should go through dedicated services.

Preferred services:

- App\Services\Entitlements\EntitlementService
- App\Services\Usage\UsageLimitService
- App\Services\Plans\PlanResolverService

Controllers must not contain entitlement logic directly.

## Architecture Rules

Use clean, maintainable Laravel architecture.

Business logic should be placed in services, actions or jobs, not directly inside controllers or Filament resources.

Preferred structure:

- app/Actions/
- app/Services/
- app/Models/
- app/Policies/
- app/Jobs/
- app/Filament/
- app/Http/
- app/Support/

Use:

- Models for data structure
- Services for business rules
- Actions for specific workflows
- Policies for authorization
- Jobs for queue-based work
- Events/listeners where useful
- Form requests for validation when appropriate
- DTOs/value objects when complexity grows

Avoid:

- Fat controllers
- Fat Filament resources
- Hard-coded plan logic
- Hard-coded UI labels
- Hidden business rules
- Fake data pretending to be real functionality
- Large unrelated rewrites

## Frontend Rules

The user-facing application must feel like a premium SaaS product.

Use:

- React
- Inertia.js
- Tailwind CSS
- Reusable components
- Responsive layouts
- Clean dashboard patterns
- Accessible forms
- Consistent spacing
- Professional empty states
- Clear loading states
- Clear error states
- Conversion-focused upgrade prompts

The frontend must include these major areas over time:

- Landing page
- Barcode generator page
- Pricing page
- User dashboard
- Barcode history page
- Saved templates page
- Billing page
- API access page
- Bulk generator page
- Account settings page

Visible text should be translation-ready wherever practical.

Do not scatter hard-coded strings across components when the text should later be managed through translations or settings.

## Admin Panel Rules

Use Filament for the admin panel.

The admin panel must be powerful, professional and B2B-ready.

Admin sections should include:

- Dashboard
- Users
- Subscriptions
- Plans & Pricing
- Features
- Usage Limits
- Barcode Categories
- Barcode Types
- Barcode Parameters
- Generated Barcodes
- Bulk Jobs
- API Management
- Payments
- Invoices
- Coupons
- Languages
- Translations
- Pages
- FAQ
- Email Templates
- Notifications
- Roles & Permissions
- System Settings
- Audit Logs
- Reports

Filament resources must not become bloated with business logic.

Use services/actions for important operations.

Admin UI should be dense enough for professional usage but still clean and readable.

## Barcode System Rules

Barcode types must be configurable.

Do not build the application assuming barcode types are fixed forever.

Barcode categories, barcode types and barcode parameters must be stored in the database.

Barcode type records should support:

- name
- slug
- category
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
- available_plan_rules
- parameter_schema
- documentation
- seo_title
- seo_description
- sort_order

Barcode parameter records should support:

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

Initial barcode types can include:

- QR Code
- Code 128
- Code 39
- EAN-13
- UPC-A
- Data Matrix
- PDF417

Do not try to implement every barcode type in the first phase.

Start with a scalable architecture.

## Barcode Generation Services

Barcode generation should be handled through dedicated services.

Preferred service structure:

- App\Services\Barcode\BarcodeGenerationService
- App\Services\Barcode\BarcodeValidationService
- App\Services\Barcode\BarcodeRendererService
- App\Services\Barcode\BarcodeExportService
- App\Services\Barcode\BarcodeTypeRegistry

The generator flow should roughly be:

1. Resolve user and plan
2. Check entitlement
3. Check usage limit
4. Resolve barcode type
5. Validate input data
6. Validate dynamic parameters
7. Generate barcode
8. Store history if allowed
9. Store/export file if needed
10. Increment usage
11. Return result

Generation, validation, usage and export must not be mixed together in one controller.

## Usage Limit Rules

Usage limits must be reliable.

The system must support:

- Daily generation limit
- Monthly generation limit
- API request limit
- Bulk generation job limit
- Bulk row limit
- Export format limits
- History retention
- File retention

Usage counters should be stored in a way that supports:

- user_id
- plan_id
- feature_key
- period_type
- period_start
- period_end
- used
- limit
- source

Sources may include:

- web
- bulk
- api
- admin

Always check limits before generating or exporting.

Always increment usage after successful generation/export.

Avoid incrementing usage for failed validation.

## Billing and Payment Rules

Payment provider architecture must be extensible.

Stripe should be the first supported provider, but the system should be designed so these can be added later:

- Stripe
- Paddle
- PayPal
- iyzico
- Manual bank transfer

Payment provider settings must be admin-manageable.

Do not hard-code payment credentials.

Use .env for secrets and database settings for provider status/configuration metadata.

The app should eventually support:

- Subscriptions
- Plan upgrades
- Plan downgrades
- Subscription cancellation
- Trials
- Invoices
- Failed payments
- Payment retries
- Coupons
- Tax/VAT fields
- Webhook logs
- Manual payment approval

Do not implement payment flows before the foundation is stable.

## Multi-Language Rules

Default language: English.

The system must be prepared for more languages, including Turkish.

Language and translation management should be admin-manageable.

The system should support:

- languages
- translation_keys
- translated_values
- page translations
- FAQ translations
- email template translations
- plan translations
- barcode type translations
- SEO translations

Avoid hard-coding user-facing labels when they clearly belong in translations.

## Security Rules

Security must not be weakened for convenience.

Use:

- Policies
- Gates
- Role-based permissions
- CSRF protection
- Rate limiting
- Signed URLs where needed
- Secure file access
- API token hashing
- Proper validation
- Audit logs for sensitive admin actions

Do not expose raw storage paths.

Do not store payment secrets in plain admin-visible fields.

Do not log full sensitive payment data.

Do not trust client-side entitlement checks.

Frontend locks are only UX. Backend must enforce all permissions and limits.

## API Rules

API access is a paid feature and must be entitlement-based.

API features should eventually include:

- API keys
- API key regeneration
- API request logs
- API rate limits
- Barcode generation endpoint
- API usage dashboard
- API documentation

API keys should be stored securely.

API rate limits must be enforced backend-side.

## Bulk Generation Rules

Bulk barcode generation is a premium feature.

It should be queue-based.

Bulk flow:

1. Upload CSV/Excel
2. Select barcode type
3. Map columns
4. Validate rows
5. Show validation preview
6. Create bulk job
7. Process in queue
8. Export results
9. Store job history

Bulk generation should support:

- CSV
- Excel
- ZIP export
- PDF sheet export
- SVG package
- PNG package
- Error row report

Do not implement heavy bulk generation before the core barcode engine is stable.

## UI/UX Style Rules

BarcodeOS must look like a premium SaaS product.

Use:

- Clean white and soft-gray backgrounds
- Navy/blue primary tones
- Emerald or violet accents only when useful
- Rounded cards
- Professional tables
- Search and filter bars
- Clear badges
- Clear plan labels
- Usage progress bars
- Empty states
- Upgrade prompts
- Responsive sidebars
- Clear dashboard widgets

Avoid:

- Childish gradients
- Random colors
- Overly playful icons
- Crowded hero sections
- Unreadable table density
- Decorative UI that does not help the product
- Mock data that looks real but is not wired

## Testing and Quality Rules

For meaningful backend work, add or update tests where practical.

Important areas to test:

- Entitlement checks
- Usage limit checks
- Plan feature access
- Barcode type validation
- Barcode generation flow
- Subscription state handling
- API authorization
- Admin permissions

Before finalizing a task, run relevant commands when possible:

- php artisan test
- php artisan migrate:fresh --seed
- npm run build
- php artisan route:list
- php artisan config:clear
- php artisan cache:clear

If a command cannot be run, clearly explain why.

## Database and Migration Rules

Use clear table names.

Use indexes for frequently queried fields.

Important likely tables:

- users
- roles
- permissions
- plans
- features
- plan_features
- subscriptions
- usage_counters
- barcode_categories
- barcode_types
- barcode_parameters
- generated_barcodes
- barcode_exports
- bulk_jobs
- api_keys
- api_usage_logs
- payment_providers
- payments
- invoices
- languages
- translations
- system_settings
- audit_logs

Do not create unnecessary tables without explaining their purpose.

Do not rename or remove existing columns casually.

For schema changes, explain impact clearly.

## Documentation Rules

Keep documentation in the docs/ directory.

Required documents:

- docs/ROADMAP.md
- docs/PHASES.md
- docs/DECISIONS.md
- docs/ARCHITECTURE.md
- docs/SETUP.md
- docs/FEATURES.md

Use documentation to track:

- Current phase
- Completed work
- Next work
- Architecture decisions
- Known limitations
- Commands
- Deployment notes

## Phase Management

Development must be incremental.

Do not attempt to build the entire SaaS in one task.

Recommended phases:

- Phase 0: Project foundation
- Phase 1: SaaS admin foundation
- Phase 2: Plans, features and usage limits
- Phase 3: Barcode type and parameter management
- Phase 4: Barcode generation engine
- Phase 5: User dashboard and history
- Phase 6: Pricing and billing foundation
- Phase 7: Stripe subscription integration
- Phase 8: Bulk barcode generation
- Phase 9: API access
- Phase 10: Multi-language management
- Phase 11: Reports, audit logs and polish
- Phase 12: Production deployment readiness

The current phase must be tracked in:

- docs/ROADMAP.md
- docs/PHASES.md

Every task should state which phase it belongs to.

## Coding Style

Prefer readable, explicit code.

Use meaningful names.

Avoid clever code that is hard to maintain.

Keep methods focused.

Use Laravel conventions unless there is a good reason not to.

Avoid large files.

Use service classes for domain logic.

Use enums where they improve clarity.

Use constants for stable internal keys, but keep business-configurable values in the database.

## Seed Data Rules

Seed only useful initial data.

Initial seed data may include:

Default languages:

- English
- Turkish

Default plans:

- Free
- Starter
- Pro
- Business
- Enterprise

Default features:

- barcode.generate
- barcode.history
- export.png
- export.svg
- export.pdf
- bulk.generate
- api.access
- watermark.remove
- team.members

Default barcode categories:

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

Do not seed fake production users unless explicitly needed for local development.

## Environment Rules

Use .env.example.

Do not commit secrets.

Required environment areas:

- APP
- DATABASE
- REDIS
- QUEUE
- MAIL
- STORAGE
- PAYMENT PROVIDERS
- BARCODE
- API

Docker should be suitable for local development.

Production deployment should remain compatible with Coolify or a standard VPS Docker workflow.

## What Not To Do

Do not:

- Build this as WordPress
- Hard-code subscription rules
- Hard-code plan features
- Hard-code payment provider logic
- Put all barcode logic in a controller
- Build all barcode types at once
- Add fake billing that looks real but does not work
- Add unsafe file URLs
- Skip authorization checks
- Skip backend entitlement checks
- Make large unrelated rewrites
- Ignore documentation
- Ignore responsive design
- Use Stitch-exported code blindly
- Build static screens disconnected from backend models

## Expected Agent Behavior

When working on this project:

1. Inspect existing structure first.
2. Identify the current phase.
3. Make the smallest safe change that advances the phase.
4. Keep admin-manageability in mind.
5. Keep entitlement checks central.
6. Keep UI premium and consistent.
7. Update documentation when architecture or phase status changes.
8. Explain changed files.
9. Explain commands to run.
10. Suggest the next safe task.

## Current Strategic Direction

The first priority is not barcode generation.

The first priority is a strong SaaS foundation:

- Project foundation
- Admin panel
- Plans
- Features
- Entitlements
- Usage limits
- Barcode type management
- System settings
- Multi-language foundation

Barcode generation should be implemented only after the data model and entitlement foundation are stable.

## Final Product Goal

BarcodeOS should become a global, subscription-based, admin-manageable barcode generation SaaS platform.

It should support free users, paid users, business users and enterprise users.

It should feel professional enough for companies that need barcode generation for:

- E-commerce
- Retail
- Manufacturing
- Warehouse
- Logistics
- Healthcare
- Product labeling
- Developer integrations
- Payment QR workflows

The product must be scalable, maintainable, secure and commercially ready.
