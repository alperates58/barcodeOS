# BarcodeOS Roadmap

## Project Vision

BarcodeOS is a standalone, global, subscription-based barcode generation SaaS platform.

The product is designed to become a professional B2B platform for:

- Admin-managed plans and feature entitlements
- Barcode generation and history
- Export controls
- Bulk processing
- API access
- Multi-language content
- Payment provider extensibility
- Operational reporting

Default product language: English.

---

## Current Strategic Priority

Current priority:

1. Finish SaaS admin foundation
2. Complete plan-feature management UX
3. Prepare subscription and usage administration
4. Keep barcode catalog management admin-driven
5. Delay real barcode rendering and billing flows until foundations are stable

This means the roadmap still prioritizes business control, admin manageability and safe deployment over feature breadth.

---

## Current Status

Recently completed:

- Laravel 12 foundation
- Inertia.js + React frontend foundation
- Tailwind CSS foundation
- Filament 5 admin panel
- Docker local development stack
- PostgreSQL-first environment example
- Redis-ready queue/cache setup
- Core SaaS models and migrations
- Core seeders and tests
- Initial Filament resources
- Public landing, dashboard and pricing placeholders backed by database data
- Safe Coolify-triggered system update page
- Plan-feature relation manager inside the plan admin workflow
- Read-only usage counter resource for admin visibility
- Hardened entitlement, plan resolution and usage summary services
- Read-only subscription resource for safe admin visibility
- Barcode access foundation for barcode-type and export-format entitlement checks
- Pre-render-only barcode validation foundation with no rendering, export, history or usage side effects
- GS1 DataMatrix parse and normalization foundation separated into a dedicated barcode service
- Expanded barcode type admin management for access and export metadata
- Dashboard usage cards now backed by real usage counter summaries
- Phase 3 started with barcode parameter admin UX and generator config foundation
- `App\Services\Barcode\ParameterSchemaResolver` now centralizes resolved parameter schema
- Read-only barcode type config endpoint added for authenticated app flows without rendering
- Responsive feature-based frontend barcode foundation added without wiring a live generator page

Current phase:

Phase 3 - Barcode Type and Parameter Management

Roadmap interpretation:

- Phase 0 is complete
- Phase 1 is completed and strengthened
- Phase 2 foundation is largely complete
- Phase 3 is current and in progress
- The barcode rendering engine remains future work

---

## Near-Term Roadmap

### Phase 1: SaaS Admin Foundation

Remaining target:

- Role and permission management screens
- Audit log resource
- Admin dashboard summaries
- Safer production admin access workflow
- Dedicated subscription administration screens

### Phase 2: Plans, Features and Usage Limits

Next build target:

- Subscription reporting polish and possible safe detail views
- Richer entitlement and usage summaries
- Keep GS1 advanced barcode access entitlement-driven through seeded barcode type metadata
- Keep GS1 parser usage explicit through `gs1-datamatrix` slug or `validation_rules.gs1_datamatrix=true`
- Barcode type and export entitlement enforcement in upcoming rendering workflows
- Wire the pre-render-only validation layer into future rendering flows without adding persistence or usage side effects
- Expand GS1 support beyond the current short `01/21/93` and long `01/21/91/92` parser foundation when rendering and broader AI coverage become relevant
- Usage reporting widgets driven by real counters

### Phase 3: Barcode Type and Parameter Management

Foundation already exists:

- Categories
- Types
- Parameters
- Seeded initial catalog
- Seeded GS1 DataMatrix metadata with separate normal Data Matrix behavior
- Shared resolved parameter schema foundation for validation and future frontend form usage
- Read-only barcode type config transport for authenticated app requests

Next expansion:

- Better parameter management UX
- Deeper type documentation and validation admin tooling
- Connect config-driven schema to a real generator page without implying rendering exists
- Keep barcode types database-driven rather than splitting them into separate Laravel modules

---

## Guardrails

Do not implement in the next step:

- Real payment checkout
- Barcode renderer integration
- Bulk generation jobs
- API barcode generation
- Fake production billing screens

Do implement in the next step:

- Admin-manageable plan mapping
- Usage administration
- Safer admin operations
- More complete reporting foundations
- Shared barcode services with category-based renderer and validator expansion points
