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
5. Start Phase 4 only through a tightly scoped QR preview slice while keeping billing and wider rendering flows deferred

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
- Phase 4 has started through Phase 4.1 with QR Code in-memory SVG preview as the first rendering slice only

Current phase:

Phase 4 - Barcode Generation Engine

Roadmap interpretation:

- Phase 0 is complete
- Phase 1 is completed and strengthened
- Phase 2 foundation is largely complete
- Phase 3 is complete enough and closure audited
- Phase 4 has started through Phase 4.1
- broad barcode rendering coverage remains future work

Phase 3 is complete enough, and Phase 4 is now active.

---

## Near-Term Roadmap

### Phase Status

- Phase 0: Completed
- Phase 1: Completed / strengthened
- Phase 2: Completed foundation
- Phase 3: Complete enough / closure audited
- Phase 4: Started / Phase 4.1 QR Preview

### Phase 4.1: QR Code In-Memory SVG Preview

Phase 4 starts with a narrow first renderer slice:

- QR Code is the only renderer-enabled barcode type in this slice
- Response output is in-memory SVG preview only
- No persistence is allowed
- No file storage is allowed
- No download is allowed
- No `generated_barcodes` records are created
- No `barcode_exports` records are created
- No usage increment is allowed
- Other barcode types must still return `renderer_not_supported`

Phase 4.1 depends on existing foundation from earlier phases:

- Barcode catalog management remains database-driven
- Parameter schema resolution already exists
- Validation is already side-effect-free
- Access-control and usage-limit services already exist
- Generator page foundation already exists for authenticated app flows

Still deferred after Phase 4.1:

- barcode history persistence
- file retention and storage flows
- downloads and export packages
- non-QR renderer support
- API and bulk generation outputs

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
- Preview only with no download path
- No history persistence
- No file persistence
- No file storage
- No `generated_barcodes`
- No `barcode_exports`
- No usage increment
- Other barcode types return `renderer_not_supported`
