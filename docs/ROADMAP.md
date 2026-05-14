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
- Phase 3 generator page foundation now connects the live config endpoint to an authenticated Inertia page
- Config-driven dynamic parameter form now exists for validate-only user flows
- Validate-only user flow is active while rendering/export/history persistence remain future work
- `App\Services\Barcode\BarcodeGenerationService` now exists as a coordinator skeleton for validation plus renderer availability checks only
- Phase 3 public homepage + generator landing foundation added as a modern SaaS discovery layer
- Public homepage now uses active barcode catalog data and public plan data without calling validation or generation flows
- Public `Barcode Generator` and `API` navigation now route to homepage anchors instead of separate public pages

Current phase:

Phase 3 - Barcode Type and Parameter Management

Roadmap interpretation:

- Phase 0 is complete
- Phase 1 is completed and strengthened
- Phase 2 foundation is largely complete
- Phase 3 is complete enough and closure audited
- Phase 4 is next and begins the rendering engine work
- The barcode rendering engine remains future work

Phase 3 is complete enough to start Phase 4 rendering work.

---

## Near-Term Roadmap

### Phase Status

- Phase 0: Completed
- Phase 1: Completed / strengthened
- Phase 2: Completed foundation
- Phase 3: Complete enough / closure audited
- Phase 4: Next / Rendering Engine

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
- Build on the validate-only generator page without implying rendering exists
- Continue turning the public website into a trustworthy discovery layer without introducing fake preview or download behavior
- Keep `BarcodeGenerationService` side-effect-free until Phase 4 introduces real rendering orchestration
- Keep barcode types database-driven rather than splitting them into separate Laravel modules

Non-blocking Phase 3 backlog:

- upgrade messaging polish
- richer field-level entitlement messages
- saved templates foundation
- generator UX polish
- barcode type documentation UX

---

## Guardrails

Do not implement in the next step:

- Real payment checkout
- Barcode renderer integration
- Bulk generation jobs
- API barcode generation
- Fake production billing screens

Do implement in the next step:

- Phase 4 first task: QR Code renderer with in-memory SVG output only
- No history persistence
- No file persistence
- No `generated_barcodes`
- No `barcode_exports`
- No usage increment
