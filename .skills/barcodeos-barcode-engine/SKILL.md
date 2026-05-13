# BarcodeOS Barcode Engine Skill

Use this skill when working on BarcodeOS barcode generation, barcode validation, barcode rendering, export formats, barcode type registry, barcode parameters, generated barcode history, bulk generation, API generation, usage checks related to generation, and barcode file storage.

BarcodeOS is a professional SaaS barcode generation platform. The barcode engine must be reliable, extensible, secure and connected to the plan/feature entitlement system.

Default language: English.

---

## 1. Core Principle

The barcode engine must not be a simple controller method.

Barcode generation must be handled through clean backend services.

The engine must support:

- Admin-managed barcode types
- Admin-managed barcode parameters
- Dynamic validation rules
- Feature entitlement checks
- Usage limit checks
- Export permission checks
- Secure file storage
- Barcode history
- Bulk generation
- API generation

---

## 2. Do Not Hard-Code Product Rules

Do not hard-code:

- Which plans can generate barcodes
- Which plans can export PDF/SVG/EPS
- Which plans can use bulk generation
- Which plans can use API generation
- Which barcode types belong to which plan
- Daily/monthly generation limits
- History retention periods
- File retention periods

Use:

- EntitlementService
- UsageLimitService
- PlanResolverService
- Database-managed barcode type metadata
- Database-managed barcode parameters

---

## 3. Required Services

Preferred service structure:

```text
App\Services\Barcode\BarcodeGenerationService
App\Services\Barcode\BarcodeValidationService
App\Services\Barcode\BarcodeRendererService
App\Services\Barcode\BarcodeExportService
App\Services\Barcode\BarcodeTypeRegistry
App\Services\Barcode\BarcodeHistoryService
```

Optional later services:

```text
App\Services\Barcode\BarcodePreviewService
App\Services\Barcode\BarcodeParameterResolver
App\Services\Barcode\BarcodeFileRetentionService
App\Services\Barcode\BarcodeLibraryAdapter
```

---

## 4. Generation Flow

The barcode generation flow should follow this order:

1. Resolve authenticated user.
2. Resolve active plan.
3. Resolve requested barcode type.
4. Confirm barcode type is active.
5. Check required feature entitlements.
6. Check selected export format entitlement.
7. Check daily/monthly usage limits.
8. Validate input data.
9. Validate dynamic parameters.
10. Render barcode.
11. Store generated file if needed.
12. Store generated barcode history if allowed.
13. Increment usage after success.
14. Return preview/export response.

Important:

- Failed validation must not increment usage.
- Failed entitlement check must not increment usage.
- Failed rendering should not increment usage unless explicitly designed otherwise.
- Backend must enforce access even if frontend hides locked features.

---

## 5. Main Data Models

Expected models:

```text
BarcodeCategory
BarcodeType
BarcodeParameter
GeneratedBarcode
BarcodeExport
UsageCounter
Plan
Feature
PlanFeature
```

Later models:

```text
BarcodeTemplate
BulkJob
BulkJobRow
ApiKey
ApiUsageLog
```

---

## 6. Barcode Categories

Initial categories:

```text
Linear / 1D
Retail
2D
GS1
Postal & Logistics
Payment & Banking
```

Admin must be able to manage categories.

Category fields may include:

- name
- slug
- description
- icon
- is_active
- sort_order
- metadata
- seo_title
- seo_description

---

## 7. Barcode Types

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

Do not implement every barcode type at once.

Start with a small reliable set, then expand.

Barcode type fields should support:

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
- required_features
- documentation
- seo_title
- seo_description
- sort_order
- metadata

Barcode types must be admin-manageable.

---

## 8. Barcode Parameters

Barcode parameters are dynamic settings for each barcode type.

Example parameters:

- width
- height
- scale
- margin
- rotation
- foreground_color
- background_color
- transparent_background
- show_human_readable_text
- font_size
- error_correction_level
- encoding_mode
- quiet_zone
- checksum
- gs1_mode

Barcode parameter fields should support:

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

Parameter types may include:

```text
text
number
integer
boolean
select
multi_select
color
json
```

---

## 9. Validation Rules

Barcode validation must be type-aware.

Validation should include:

- Required data
- Maximum length
- Minimum length
- Allowed characters
- Numeric-only rules
- Check digit rules
- GS1 application identifier rules, later
- Export format allowed
- Parameter range validation
- Parameter option validation

Examples:

### QR Code

- Accepts general text.
- May allow URL, email, phone, Wi-Fi or vCard payloads later.
- Error correction parameter may be allowed.

### Code 128

- Supports ASCII data depending on implementation.
- Validate maximum length based on configured rules.

### Code 39

- Validate supported characters.
- Usually supports uppercase letters, numbers and limited symbols.

### EAN-13

- Numeric only.
- 12 or 13 digits depending on check digit handling.
- Check digit validation/generation should be explicit.

### UPC-A

- Numeric only.
- 11 or 12 digits depending on check digit handling.

### Data Matrix

- General text or binary depending on library support.
- Advanced validation later.

### PDF417

- General text.
- Rows/columns/security level parameters may be supported later.

---

## 10. Barcode Type Registry

BarcodeTypeRegistry maps database barcode type records to renderer implementations.

Example responsibility:

```text
qr-code -> QrCodeRenderer
code-128 -> Code128Renderer
ean-13 -> Ean13Renderer
```

The registry should:

- Resolve supported renderer.
- Fail gracefully when a barcode type is configured but not implemented.
- Keep UI/admin configuration separate from actual rendering support.
- Allow future barcode types without rewriting the generation flow.

If a barcode type exists in database but no renderer exists, show a clear backend error:

```text
This barcode type is configured but not yet supported by the rendering engine.
```

---

## 11. Renderer Rules

BarcodeRendererService should wrap the underlying barcode library.

Do not let external library calls spread across controllers or jobs.

The renderer should accept:

- Barcode type
- Data
- Normalized parameters
- Output format

The renderer should return:

- Raw content, or
- Temporary file, or
- Value object with file/content metadata

Possible value object:

```text
RenderedBarcode
```

Suggested fields:

- format
- mime_type
- content
- width
- height
- metadata

---

## 12. Export Format Rules

Supported export formats over time:

```text
png
svg
pdf
eps
zip
```

Initial practical formats:

```text
png
svg
```

Every export format must be entitlement-checked.

Feature mapping:

```text
png -> export.png
svg -> export.svg
pdf -> export.pdf
eps -> export.eps
zip -> export.zip
```

Backend must enforce export permissions.

Frontend may show locked export buttons, but that is only UX.

---

## 13. File Storage Rules

Generated barcode files may be stored depending on plan/history settings.

Storage rules:

- Use local disk for development.
- Use S3-compatible storage for production.
- Store file metadata in database.
- Do not expose raw storage paths.
- Use secure download routes.
- Use signed temporary URLs only when appropriate.
- Enforce authorization on downloads.

Generated file metadata may include:

- user_id
- generated_barcode_id
- disk
- path
- filename
- mime_type
- format
- size
- checksum
- expires_at

---

## 14. Generated Barcode History

GeneratedBarcode should store enough data to support:

- User history
- Duplicate generation
- Download previous output
- Usage reporting
- Admin review
- Audit/debugging

Suggested fields:

- user_id
- barcode_type_id
- plan_id
- source
- input_preview
- input_hash
- parameters
- export_format
- status
- generated_at
- expires_at
- metadata

Do not store highly sensitive full data unless necessary.

Consider storing:

- input_hash
- safe preview
- encrypted full payload, if needed later

---

## 15. Usage Limit Integration

BarcodeGenerationService must use UsageLimitService.

Check:

- daily_generation_limit
- monthly_generation_limit
- source-specific limits where needed

Usage source values:

```text
web
api
bulk
admin
```

Rules:

- Web generation increments web source usage.
- API generation increments api source usage.
- Bulk generation increments bulk source usage or row-level generation usage depending on design.
- Admin generation may be tracked separately.

Avoid double-counting.

Document the usage counting strategy when implemented.

---

## 16. Entitlement Integration

Barcode generation must check:

- barcode.generate
- barcode-specific required features
- export format feature
- bulk.generate for bulk jobs
- api.access for API generation
- advanced feature keys when applicable

Examples:

- QR Code web generation requires barcode.generate.
- PDF export requires export.pdf.
- Business API generation requires api.access.
- GS1 advanced features require gs1.advanced.
- Bulk CSV generation requires bulk.generate and bulk.csv_upload.

---

## 17. Bulk Generation Engine

Bulk generation must be queue-based.

Bulk generation flow:

1. User uploads CSV/Excel.
2. System validates file type and size.
3. User maps columns.
4. System validates rows.
5. System creates BulkJob.
6. Queue processes rows.
7. Each row uses the core BarcodeGenerationService or shared lower-level services.
8. Output files are collected.
9. ZIP/PDF package is created.
10. Errors are saved.
11. Job status is updated.
12. User is notified.

Bulk job statuses:

```text
pending
validating
queued
processing
completed
failed
cancelled
```

Bulk errors should be understandable.

Do not run large bulk work synchronously in a web request.

---

## 18. API Generation Engine

API generation should reuse the same backend services.

API flow:

1. Validate API key.
2. Resolve user.
3. Check api.access.
4. Check API rate limit.
5. Check API usage limit.
6. Validate barcode type.
7. Validate data and parameters.
8. Generate barcode.
9. Save API usage log.
10. Increment usage.
11. Return response.

API must not bypass entitlement or usage rules.

---

## 19. Admin Management

Admin should be able to manage:

- Barcode categories
- Barcode types
- Barcode parameters
- Default values
- Validation rules
- Export formats
- Required features
- Visibility/status
- Documentation
- SEO fields

Admin should not directly edit dangerous internal renderer class names unless carefully controlled.

Use safe keys/slugs and registry mapping.

---

## 20. Library Selection Rules

Do not choose a barcode library casually.

When selecting libraries, consider:

- QR support
- 1D barcode support
- 2D barcode support
- SVG support
- PNG support
- PDF support
- License
- Maintenance
- Laravel/PHP compatibility
- Performance
- Extensibility
- GS1 support

Document final library decisions in:

```text
docs/DECISIONS.md
```

Do not assume one library supports all barcode types.

A registry/adapter pattern may be needed.

---

## 21. Error Handling

Errors should be clear and safe.

Examples:

- Invalid barcode data.
- Unsupported barcode type.
- Export format not allowed for your plan.
- Daily generation limit reached.
- This barcode type is not available on your plan.
- Renderer not implemented for this barcode type.
- File could not be generated.

Do not expose internal stack traces to users.

Do not show raw library errors directly.

---

## 22. UI Integration Rules

The frontend generator should receive:

- Available barcode categories
- Available barcode types
- Dynamic parameters
- User's plan limits
- Current usage
- Export format availability
- Locked feature info

Frontend should show:

- Barcode type selector
- Data input
- Dynamic parameter form
- Preview panel
- Export buttons
- Usage progress
- Locked feature prompts
- Validation errors

Backend remains source of truth.

---

## 23. Testing Requirements

Test the barcode engine.

Important tests:

- Valid QR code generation.
- Invalid data returns validation error.
- Unsupported barcode type returns safe error.
- Free user cannot export PDF.
- User over daily limit cannot generate.
- Failed validation does not increment usage.
- Successful generation increments usage.
- Generated barcode history is saved.
- User cannot download another user's barcode file.
- API generation requires api.access.
- Bulk generation requires bulk.generate.

---

## 24. Performance Notes

Barcode generation may become heavy.

Plan for:

- Queued bulk jobs
- File cleanup
- Cached barcode type configuration
- Efficient usage counter queries
- Avoiding unnecessary file writes for previews
- Storage cleanup jobs
- Rate limits for API and free users

---

## 25. Security Notes

Protect:

- User-generated files
- API keys
- Billing-related data
- Admin configuration
- Storage paths

Do not:

- Expose raw file paths
- Trust frontend plan checks
- Store API keys in plain text
- Log full sensitive barcode payloads unless required
- Allow arbitrary renderer class execution from admin input
- Allow unlimited unauthenticated generation without rate limits

---

## 26. Initial Implementation Target

When first implementing the barcode engine, do not start with every barcode type.

Start with:

1. QR Code
2. Code 128

Then add:

3. Code 39
4. EAN-13
5. UPC-A
6. Data Matrix
7. PDF417

Initial export formats:

1. PNG
2. SVG

Later:

3. PDF
4. EPS
5. ZIP

---

## 27. Strong Warnings

Do not:

- Put generation logic in a controller.
- Skip entitlement checks.
- Skip usage checks.
- Increment usage before validation succeeds.
- Store raw storage URLs.
- Implement every barcode type at once.
- Trust frontend locked states.
- Hard-code plan names.
- Allow admin to configure unsafe renderer classes.
- Build bulk generation before queue foundation exists.
