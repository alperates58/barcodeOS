# BarcodeOS Features

This document defines the planned feature system for BarcodeOS.

The feature system is one of the core parts of the SaaS architecture.

Business access must be controlled by feature keys and limits, not by hard-coded plan names.

---

## 1. Feature System Purpose

BarcodeOS must support:

- Free users
- Paid users
- Business users
- Enterprise users
- Custom future plans
- Plan-specific barcode access
- Plan-specific export access
- Plan-specific API access
- Plan-specific bulk generation access
- Plan-specific usage limits

To support this, the application must use a feature entitlement system.

---

## 2. Core Rule

Do not check plan names directly in business logic.

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

Bad:

```php
if ($user->plan === 'business') {
    $limit = 50000;
}
```

Good:

```php
$limit = $usageLimitService->limitFor($user, 'monthly_generation_limit');
```

---

## 3. Feature Categories

The initial feature set is grouped into these categories:

1. Barcode generation
2. Export formats
3. Barcode history and templates
4. Bulk generation
5. API access
6. Team features
7. Billing features
8. Advanced barcode features
9. Admin/system features
10. Branding and watermark features

---

## 4. Initial Feature Keys

## 4.1 Barcode Generation Features

### barcode.generate

Allows the user to generate barcodes through the web interface.

Recommended availability:

- Free: yes
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### barcode.history

Allows the user to save and view generated barcode history.

Recommended availability:

- Free: limited
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### barcode.template.save

Allows the user to save barcode configuration templates.

Recommended availability:

- Free: no
- Starter: no or limited
- Pro: yes
- Business: yes
- Enterprise: yes

### barcode.advanced_parameters

Allows access to advanced barcode settings.

Examples:

- advanced margins
- rotation
- encoding mode
- quiet zone control
- checksum options
- text display settings

Recommended availability:

- Free: no
- Starter: limited
- Pro: yes
- Business: yes
- Enterprise: yes

---

## 4.2 Export Format Features

### export.png

Allows PNG export.

Recommended availability:

- Free: yes
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### export.svg

Allows SVG export.

Recommended availability:

- Free: no or limited
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### export.pdf

Allows PDF export.

Recommended availability:

- Free: no
- Starter: no or limited
- Pro: yes
- Business: yes
- Enterprise: yes

### export.eps

Allows EPS export.

Recommended availability:

- Free: no
- Starter: no
- Pro: no or optional
- Business: yes
- Enterprise: yes

### export.zip

Allows ZIP export packages.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes for bulk results
- Business: yes
- Enterprise: yes

---

## 4.3 Bulk Generation Features

### bulk.generate

Allows the user to use bulk barcode generation.

Recommended availability:

- Free: no
- Starter: no or limited
- Pro: yes
- Business: yes
- Enterprise: yes

### bulk.csv_upload

Allows CSV upload for bulk generation.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes
- Business: yes
- Enterprise: yes

### bulk.excel_upload

Allows Excel upload for bulk generation.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes
- Business: yes
- Enterprise: yes

### bulk.pdf_sheet_export

Allows bulk results to be exported as PDF sheets.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes
- Business: yes
- Enterprise: yes

### bulk.error_report

Allows downloading error reports for failed bulk rows.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes
- Business: yes
- Enterprise: yes

---

## 4.4 API Features

### api.access

Allows the user to use the BarcodeOS API.

Recommended availability:

- Free: no
- Starter: no
- Pro: no or optional
- Business: yes
- Enterprise: yes

### api.keys.manage

Allows the user to create and manage API keys.

Recommended availability:

- Free: no
- Starter: no
- Pro: no or optional
- Business: yes
- Enterprise: yes

### api.logs.view

Allows the user to view API request logs.

Recommended availability:

- Free: no
- Starter: no
- Pro: no
- Business: yes
- Enterprise: yes

### api.advanced_rate_limits

Allows higher or custom API limits.

Recommended availability:

- Free: no
- Starter: no
- Pro: no
- Business: limited
- Enterprise: yes

---

## 4.5 Team Features

### team.members

Allows multiple team members under one account or workspace.

Recommended availability:

- Free: no
- Starter: no
- Pro: optional
- Business: yes
- Enterprise: yes

### team.roles

Allows assigning roles to team members.

Recommended availability:

- Free: no
- Starter: no
- Pro: no
- Business: yes
- Enterprise: yes

### team.invites

Allows inviting users to a team.

Recommended availability:

- Free: no
- Starter: no
- Pro: optional
- Business: yes
- Enterprise: yes

---

## 4.6 Billing Features

### billing.invoices

Allows the user to view and download invoices.

Recommended availability:

- Free: no
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### billing.tax_fields

Allows user/company tax information on billing profile.

Recommended availability:

- Free: no
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### billing.manual_payment

Allows admin-approved manual bank transfer payment.

Recommended availability:

- Free: no
- Starter: optional
- Pro: optional
- Business: optional
- Enterprise: yes

---

## 4.7 Advanced Barcode Features

### gs1.advanced

Allows advanced GS1 barcode features.

Recommended availability:

- Free: no
- Starter: no
- Pro: optional
- Business: yes
- Enterprise: yes

### barcode.payment_qr

Allows payment QR types such as EPC QR, Swiss QR and ZATCA QR.

Recommended availability:

- Free: limited or no
- Starter: optional
- Pro: yes
- Business: yes
- Enterprise: yes

### barcode.retail_types

Allows retail barcode types such as EAN-13, EAN-8, UPC-A and UPC-E.

Recommended availability:

- Free: limited
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### barcode.logistics_types

Allows postal and logistics barcode types.

Recommended availability:

- Free: no
- Starter: optional
- Pro: yes
- Business: yes
- Enterprise: yes

---

## 4.8 Branding and Watermark Features

### watermark.remove

Allows removing BarcodeOS watermark from exports if watermark is enabled for free users.

Recommended availability:

- Free: no
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

### branding.custom_logo

Allows adding a custom logo to supported QR/barcode outputs where technically valid.

Recommended availability:

- Free: no
- Starter: no
- Pro: yes
- Business: yes
- Enterprise: yes

### branding.custom_colors

Allows custom foreground/background colors.

Recommended availability:

- Free: limited
- Starter: yes
- Pro: yes
- Business: yes
- Enterprise: yes

---

## 5. Initial Limit Keys

Limits are numeric values attached to plans or plan-feature records.

## 5.1 Generation Limits

### daily_generation_limit

How many barcodes a user can generate per day.

Suggested defaults:

- Free: 10
- Starter: 100
- Pro: 1000
- Business: 5000
- Enterprise: custom

### monthly_generation_limit

How many barcodes a user can generate per month.

Suggested defaults:

- Free: 300
- Starter: 1000
- Pro: 10000
- Business: 50000
- Enterprise: custom

---

## 5.2 API Limits

### api_monthly_request_limit

How many API requests are allowed per month.

Suggested defaults:

- Free: 0
- Starter: 0
- Pro: 0 or optional
- Business: 50000
- Enterprise: custom

### api_rate_limit_per_minute

How many API requests are allowed per minute.

Suggested defaults:

- Free: 0
- Starter: 0
- Pro: 0 or optional
- Business: 120
- Enterprise: custom

---

## 5.3 Bulk Limits

### bulk_monthly_job_limit

How many bulk jobs can be created per month.

Suggested defaults:

- Free: 0
- Starter: 0
- Pro: 50
- Business: 250
- Enterprise: custom

### bulk_max_rows_per_job

Maximum number of rows per bulk job.

Suggested defaults:

- Free: 0
- Starter: 0
- Pro: 1000
- Business: 10000
- Enterprise: custom

---

## 5.4 Retention Limits

### history_retention_days

How many days generated barcode history is retained.

Suggested defaults:

- Free: 7
- Starter: 30
- Pro: 180
- Business: 365
- Enterprise: custom

### file_retention_days

How many days generated export files are retained.

Suggested defaults:

- Free: 1
- Starter: 30
- Pro: 180
- Business: 365
- Enterprise: custom

---

## 5.5 Team Limits

### team_member_limit

How many team members a plan allows.

Suggested defaults:

- Free: 1
- Starter: 1
- Pro: 3
- Business: 10
- Enterprise: custom

---

## 6. Suggested Plan Matrix

This matrix is a starting point. Final commercial decisions can be changed from admin panel.

| Feature / Limit | Free | Starter | Pro | Business | Enterprise |
|---|---:|---:|---:|---:|---:|
| barcode.generate | yes | yes | yes | yes | yes |
| daily_generation_limit | 10 | 100 | 1000 | 5000 | custom |
| monthly_generation_limit | 300 | 1000 | 10000 | 50000 | custom |
| export.png | yes | yes | yes | yes | yes |
| export.svg | no | yes | yes | yes | yes |
| export.pdf | no | no/limited | yes | yes | yes |
| export.eps | no | no | optional | yes | yes |
| barcode.history | limited | yes | yes | yes | yes |
| barcode.template.save | no | optional | yes | yes | yes |
| watermark.remove | no | yes | yes | yes | yes |
| bulk.generate | no | no | yes | yes | yes |
| api.access | no | no | optional | yes | yes |
| team.members | no | no | optional | yes | yes |
| gs1.advanced | no | no | optional | yes | yes |
| billing.invoices | no | yes | yes | yes | yes |

---

## 7. Database Design Suggestions

The exact schema may evolve, but the feature system should support these concepts.

## 7.1 features

Purpose:

Stores all available feature keys.

Suggested fields:

- id
- key
- name
- description
- category
- value_type
- is_active
- sort_order
- created_at
- updated_at

Example value_type:

- boolean
- integer
- string
- json

## 7.2 plans

Purpose:

Stores subscription plans.

Suggested fields:

- id
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
- created_at
- updated_at

## 7.3 plan_features

Purpose:

Maps features and limits to plans.

Suggested fields:

- id
- plan_id
- feature_id
- enabled
- value
- limit_value
- metadata
- created_at
- updated_at

The same table can support boolean features and numeric limits if designed carefully.

Alternative:

- plan_features for boolean access
- plan_limits for numeric limits

Codex may choose the cleaner option, but the decision should be documented.

## 7.4 usage_counters

Purpose:

Tracks usage by period.

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

---

## 8. Entitlement Service Contract

The entitlement service should answer questions like:

```php
$entitlementService->allows($user, 'export.pdf');

$entitlementService->value($user, 'monthly_generation_limit');

$entitlementService->featuresFor($user);

$entitlementService->cannot($user, 'bulk.generate');
```

Expected behavior:

- Resolve the user's active plan.
- Check whether feature exists.
- Check whether feature is enabled for plan.
- Return `false` when the feature is unknown, inactive or disabled on the plan.
- Return boolean or configured value.
- Avoid hard-coded plan names.

---

## 9. Usage Limit Service Contract

The usage limit service should answer questions like:

```php
$usageLimitService->canUse($user, 'barcode.generate', source: 'web');

$usageLimitService->remaining($user, 'daily_generation_limit');

$usageLimitService->increment($user, 'barcode.generate', source: 'web');

$usageLimitService->summary($user);
```

Expected behavior:

- Resolve correct usage period.
- Resolve user's plan limit.
- Check usage before operation without creating a usage counter record.
- Increment usage after successful operation and create/update usage counters only at that point.
- Avoid incrementing failed validations.
- Support web, api, bulk and admin sources.

Validation boundary note:

- pre-render validation must remain side-effect-free
- validation must not create `usage_counters`
- validation must not create `generated_barcodes` or `barcode_exports`
- unknown parameters should be ignored safely unless a barcode type explicitly promotes them into supported schema later

---

## 10. Barcode Feature Access

Barcode types may have plan/feature requirements.

Examples:

- QR Code may require barcode.generate.
- GS1 DataMatrix may require gs1.advanced.
- API generation requires api.access.
- PDF export requires export.pdf.
- Bulk generation requires bulk.generate.

Barcode type records should include metadata to connect barcode availability to feature keys.

Example:

```json
{
  "required_features": ["barcode.generate"],
  "advanced_required_features": ["gs1.advanced"]
}
```

Do not hard-code barcode availability by plan name.

Current implementation note:

- `App\Services\Barcode\BarcodeAccessService` now evaluates barcode type access through `EntitlementService`
- `barcode.generate` is always treated as the base required feature, even when a barcode type has no explicit `required_features`
- multiple `required_features` are enforced with AND logic
- barcode types remain database-driven records, not separate Laravel modules
- `gs1-datamatrix` is seeded as a separate barcode type and requires `gs1.advanced`
- normal `data-matrix` remains a standard Data Matrix type and is not treated as GS1 unless explicitly configured
- authenticated app flows can now fetch read-only barcode type config, including resolved parameter schema and export entitlement metadata

---

## 11. Export Feature Access

Every export type should be checked.

Examples:

- PNG requires export.png
- SVG requires export.svg
- PDF requires export.pdf
- EPS requires export.eps
- ZIP package requires export.zip

Backend must enforce export permissions.

Frontend should only hide/lock buttons for better UX, not for real security.

Current implementation note:

- export entitlement foundation is now centralized in `BarcodeAccessService`
- the current format map is `png`, `svg`, `pdf`, `eps`, `zip`
- unknown export formats are rejected safely

---

## 12. Upgrade Prompt Rules

When a feature is locked, UI should show a professional upgrade prompt.

Examples:

- "PDF export is available on Pro and higher plans."
- "Bulk generation is available on Pro and Business plans."
- "API access is available on Business and Enterprise plans."

Upgrade prompts should be:

- Clear
- Professional
- Non-aggressive
- Linked to pricing or billing upgrade flow

---

## 13. Feature Seeding Strategy

Initial seeder should create feature keys consistently.

Seeders should be idempotent.

They should use updateOrCreate or equivalent logic.

Seeded features should not overwrite admin changes casually unless intentionally designed.

Recommended initial seeder:

- FeatureSeeder
- PlanSeeder
- PlanFeatureSeeder

---

## 14. Admin Feature Management

Admin should be able to:

- View features
- Create features, if allowed
- Edit feature display metadata
- Enable/disable features
- Assign features to plans
- Set numeric limit values
- Set feature visibility
- Set sort order

Current implementation note:

- Plan-feature assignments are managed from the `PlanResource` edit screen through a relation manager rather than a separate plan limits table.

Admin should not need a developer to:

- Change Free daily limit
- Enable PDF for Starter
- Disable API for Business
- Create a new Enterprise-like plan
- Change history retention days

---

## 15. Testing Requirements

Feature system tests should include:

- Free user can access barcode.generate.
- Free user cannot access export.pdf.
- Pro user can access export.pdf.
- Business user can access api.access.
- User without bulk.generate cannot create bulk job.
- Daily limit prevents overuse.
- Monthly limit prevents overuse.
- Failed validation does not increment usage.
- Successful generation increments usage.
- Plan change updates entitlement result.

---

## 16. Future Feature Ideas

These are not required in early phases.

Possible future feature keys:

- analytics.advanced
- white_label.enabled
- custom_domain.enabled
- sso.enabled
- audit_logs.view
- priority_support
- dedicated_support
- custom_contract
- barcode.batch_api
- export.vector_package
- branding.white_label
- compliance.reports
- webhooks.outbound
- integrations.zapier
- integrations.make
- integrations.n8n

---

## 17. Current Feature System Status

Current status:

- Feature system design documented
- Core plans, features, plan_features and usage_counters are implemented and seeded
- EntitlementService, PlanResolverService and UsageLimitService are implemented and test-covered
- Subscription paid-access eligibility rules are centralized on the `Subscription` model
- Subscription visibility is available in a read-only admin-safe Filament resource
- Plan-feature assignments are manageable from admin
- Usage counters are visible from a read-only admin resource
- BarcodeValidationService exists as a pre-render-only validation layer
- BarcodeAccessService exists as a pre-rendering access foundation, but no barcode rendering engine is implemented yet
- Validation currently ignores unknown parameters safely and does not render, export, persist history/files or increment usage
- `App\Services\Barcode\ParameterSchemaResolver` now makes `barcode_types.parameter_schema` the primary source and active `barcode_parameters` the complementary source
- future generator UI foundations are expected to consume the resolved parameter schema instead of raw admin storage structures
- GS1 DataMatrix parsing now exists as a dedicated `App\Services\Barcode\Gs1Parser` foundation
- GS1 DataMatrix is seeded as a dedicated barcode type under the GS1 category with explicit validation metadata
- GS1 parsing is separate from entitlement, usage, billing, rendering and persistence
- The parser distinguishes normal Data Matrix from GS1 DataMatrix and supports parenthesized AI input plus literal `\F` normalization
- GS1 parsing is activated only by explicit barcode type slug signals or `validation_rules.gs1_datamatrix=true`
- Only the supported short `01/21/93` and long `01/21/91/92` GS1 DataMatrix structures are implemented in this phase
- Full GS1 AI catalog support and barcode rendering remain future work
- No PNG, SVG, PDF, EPS or ZIP generation is implemented yet; export format metadata remains admin-manageable catalog configuration only
- the barcode type config endpoint is read-only and render-free
- the authenticated generator page now consumes that config endpoint client-side to build a config-driven dynamic parameter form
- the authenticated validate-only endpoint returns structured validation results without side effects
- validate-only user flow is active, but rendering/export/history persistence remain future work
- Deeper integration into barcode generation, export and API workflows remains pending

Next implementation target:

- Expand safe subscription administration visibility as needed.
- Enforce feature checks in barcode/export workflows as later phases activate.
- Introduce shared barcode rendering and validation expansion points by category instead of separate Laravel modules per barcode type.
- Expand usage reporting and entitlement-driven UI messaging.
