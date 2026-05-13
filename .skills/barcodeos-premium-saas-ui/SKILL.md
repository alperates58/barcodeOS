# BarcodeOS Premium SaaS UI Skill

Use this skill when designing or implementing BarcodeOS user-facing screens, admin screens, dashboards, landing pages, pricing pages, forms, tables, navigation, empty states, upgrade prompts, billing pages, API pages, and barcode generator UI.

BarcodeOS must look like a polished, trustworthy, global B2B SaaS product.

Default language: English.

---

## 1. Product UI Identity

BarcodeOS is not a hobby barcode tool.

It is a professional SaaS platform for:

- Barcode generation
- Barcode history
- Bulk barcode generation
- API access
- Subscription plans
- Business and enterprise workflows

The UI must feel suitable for:

- E-commerce teams
- Retail teams
- Manufacturing teams
- Warehouses
- Logistics teams
- Healthcare labeling
- Developers
- Enterprise users

---

## 2. Visual Direction

Use a premium SaaS visual style.

Preferred style:

- Clean
- Modern
- Trustworthy
- Professional
- B2B-ready
- Dashboard-friendly
- Conversion-focused
- Responsive

Use:

- White backgrounds
- Soft gray surfaces
- Navy and blue primary tones
- Emerald or violet accents only when useful
- Rounded cards
- Subtle shadows
- Clear hierarchy
- Professional spacing
- Clean typography
- Calm icons
- Strong table/filter patterns

Avoid:

- Childish gradients
- Random bright colors
- Overly playful illustrations
- Generic crypto-style UI
- Crowded hero sections
- Tiny unreadable text
- Excessive decorative shapes
- Static mockup sections not connected to product value

---

## 3. UI Priorities

The UI should make these things obvious:

1. What BarcodeOS does
2. Which barcode type is selected
3. What data the user entered
4. Whether the barcode is valid
5. What export formats are available
6. What usage limit remains
7. Which features are locked by plan
8. How to upgrade
9. How to access history, billing, bulk and API features
10. What admins can manage

---

## 4. Layout System

Use reusable layout components.

Recommended layouts:

```text
PublicLayout
AppLayout
AuthLayout
GeneratorLayout
BillingLayout
Admin-related layout via Filament
```

Public layout:

- Header
- Main content
- Footer
- Language switcher
- Login / Get Started buttons

App layout:

- Sidebar
- Topbar
- User menu
- Plan badge
- Usage indicator
- Main content area
- Responsive mobile navigation

Generator layout:

- Left configuration panel
- Right preview panel
- Usage indicator
- Export action area
- Mobile stacked layout

Dashboard layout:

- Summary cards
- Usage charts
- Recent activity
- Quick actions
- Table/list sections

---

## 5. Color Guidance

Preferred palette concept:

- Primary: Navy / Blue
- Background: White / Soft Gray
- Success: Emerald
- Warning: Amber
- Danger: Red
- Premium/Upgrade: Violet or Blue
- Text: Slate / Neutral

Use color to communicate:

- Plan status
- Usage status
- Validation status
- Locked features
- Successful generation
- Failed validation

Do not overuse accent colors.

---

## 6. Typography Guidance

Use modern sans-serif typography.

Text hierarchy:

- Large hero headings
- Clear section headings
- Readable body text
- Compact but readable table text
- Small muted metadata text

Avoid:

- Overly thin text
- Low contrast text
- Too many font sizes
- Long dense paragraphs in UI

---

## 7. Core Components

Create reusable components where practical.

Recommended components:

```text
Button
Card
Badge
PlanBadge
UsageProgress
FeatureLockBadge
UpgradePrompt
EmptyState
LoadingState
ErrorState
PageHeader
SectionHeader
SearchInput
FilterBar
DataTableShell
MetricCard
PricingCard
FeatureComparisonTable
BarcodePreview
BarcodeTypeSelector
BarcodeParameterForm
ExportFormatSelector
LanguageSwitcher
ThemeToggle
```

Do not duplicate UI patterns across pages.

---

## 8. Public Website UI

Public pages should be conversion-focused.

Important public pages:

- Landing page
- Pricing page
- Barcode type listing page
- API page
- FAQ/help page
- Login/register

Landing page sections:

1. Header
2. Hero
3. Live barcode preview
4. Supported barcode types
5. How it works
6. Use cases
7. Pricing preview
8. API section
9. FAQ
10. Footer

Hero message:

```text
Create Professional Barcodes in Seconds
```

Hero sub-message:

```text
Generate 1D, 2D, QR, GS1, retail, logistics and payment barcodes with export-ready quality, usage limits, bulk tools and API access.
```

CTA buttons:

- Start for Free
- View Pricing

The landing page should clearly show this is a real product, not just a small utility.

---

## 9. Barcode Generator UI

The barcode generator is the most important product screen.

Desktop layout:

- Left panel: configuration
- Right panel: live preview

Left panel should include:

- Barcode type selector
- Category filter
- Data input
- Example data button
- Dynamic parameters
- Width
- Height
- Scale
- Margin
- Rotation
- Foreground color
- Background color
- Transparent background toggle
- Human readable text toggle
- Font size
- Error correction level when relevant
- Encoding mode when relevant
- Quiet zone when relevant
- Output format selector
- Generate button
- Reset button

Right panel should include:

- Large barcode preview
- Selected barcode type badge
- Validation status
- Usage limit status
- Export buttons
- Save to history button
- Copy data button
- Copy embed code button if supported
- Upgrade CTA for locked features

Mobile layout:

- Configuration first
- Preview second
- Sticky generate button if useful
- Export actions after preview

Generator UI rules:

- Make the current barcode type obvious.
- Make validation errors clear.
- Make locked export formats visible but disabled with explanation.
- Do not hide usage limits.
- Do not allow UI to imply access if backend will block it.

---

## 10. Usage Limit UI

Usage limits should be visible and understandable.

Use components like:

```text
Daily usage: 7 / 10
Monthly usage: 842 / 10,000
API usage: 1,240 / 50,000
```

Use progress bars where useful.

States:

- Normal
- Near limit
- Limit reached
- Plan upgrade available

Limit reached modal:

Title:

```text
Daily limit reached
```

Text:

```text
You have reached your free daily barcode generation limit. Upgrade your plan to continue generating without waiting.
```

Buttons:

- Upgrade Plan
- View Usage
- Come Back Tomorrow

Keep upgrade prompts professional, not aggressive.

---

## 11. Pricing UI

Pricing page should be clear and commercial.

Plans:

- Free
- Starter
- Pro
- Business
- Enterprise

Pricing UI should include:

- Monthly/yearly toggle
- Save percentage badge for yearly
- Recommended plan badge
- Feature comparison table
- FAQ section
- Payment provider trust badges
- Tax/invoice-ready messaging

Plan card content:

- Plan name
- Short description
- Price
- Billing cycle
- Main limits
- Key features
- CTA button
- Locked/unavailable features if useful

Do not hard-code plan details in frontend when real plan data exists.

Plans should be rendered from backend data.

---

## 12. User Dashboard UI

Dashboard should show:

- Current plan
- Daily usage
- Monthly usage
- API usage, if available
- Bulk usage, if available
- Renewal date
- Upgrade button
- Recent generated barcodes
- Saved templates
- Quick actions
- Usage trends later

Dashboard cards:

```text
Current Plan
Daily Usage
Monthly Usage
API Requests
Bulk Jobs
Recent Barcodes
```

Recent barcode list should show:

- Barcode preview
- Barcode type
- Created date
- Export format
- Download action
- Duplicate action
- Delete action

Empty state example:

```text
No barcodes generated yet.
Create your first barcode to see it here.
```

---

## 13. Billing UI

Billing screens should feel trustworthy.

Billing page should show:

- Current plan
- Billing cycle
- Next renewal
- Payment method
- Billing address
- Tax/VAT fields
- Invoice list
- Upgrade/downgrade buttons
- Cancel subscription flow
- Failed payment warning if needed

Invoice table should show:

- Invoice number
- Date
- Amount
- Status
- Download action

Avoid fake payment success UI unless real payment integration exists.

---

## 14. API Access UI

API page should be developer-friendly.

Show:

- API access status
- API key card
- API key create/regenerate action
- API usage
- Rate limit
- Documentation link
- Recent API requests
- Error logs
- Code examples

Code tabs:

- cURL
- JavaScript
- PHP
- Python

Locked state:

```text
API access is available on Business and Enterprise plans.
```

Button:

```text
Upgrade to Business
```

---

## 15. Bulk Generation UI

Bulk generation should feel powerful and safe.

Show:

- Upload CSV/Excel card
- Barcode type selector
- Column mapping step
- Validation preview table
- Error rows panel
- Generate bulk job button
- Job progress
- Download result
- Bulk job history

Locked state:

```text
Bulk generation is available on Pro and Business plans.
```

Button:

```text
Upgrade to Pro
```

Bulk UI must show validation clearly before job creation.

---

## 16. Admin UI Guidance

Admin panel uses Filament.

Admin should feel professional and dense, not playful.

Admin resources should include:

- Useful table columns
- Search
- Filters
- Status badges
- Row actions
- Bulk actions only when safe
- Relation managers when useful
- Clear create/edit forms

Important admin sections:

```text
Users
Plans
Features
Usage Limits
Subscriptions
Barcode Categories
Barcode Types
Barcode Parameters
Generated Barcodes
Bulk Jobs
API Management
Payment Providers
Payments
Invoices
Coupons
Languages
Translations
System Settings
Audit Logs
Reports
```

Admin dashboard widgets:

- Total users
- Active subscriptions
- Monthly recurring revenue
- Barcode generations today
- API requests this month
- Failed payments
- Top barcode types
- Recent users
- Recent payments

Do not put complex business logic inside Filament resources.

---

## 17. Tables and Filters

Use professional table UX.

Tables should include:

- Search
- Filters
- Sortable columns
- Status badges
- Date columns
- Clear row actions
- Empty states

Useful filters:

- Status
- Plan
- Barcode type
- Date range
- User
- Source
- Payment status
- API key status

Avoid overwhelming tables with too many columns.

Use detail pages where necessary.

---

## 18. Forms

Forms should be clean and grouped.

Use:

- Section cards
- Clear labels
- Help text
- Validation messages
- Required indicators
- Toggle switches
- Selects with search for long lists
- JSON/code fields only when necessary

For admin forms, group fields by meaning.

Example plan form sections:

- Basic information
- Pricing
- Limits
- Features
- Visibility
- Metadata

Example barcode type form sections:

- Basic information
- Validation
- Defaults
- Export formats
- Plan/feature access
- Documentation
- SEO

---

## 19. Empty, Loading and Error States

Every major screen should have clear states.

Empty state examples:

```text
No generated barcodes yet.
No API requests yet.
No bulk jobs yet.
No invoices yet.
No saved templates yet.
```

Loading states:

- Skeleton cards
- Spinner only when appropriate
- Disabled buttons during requests

Error states:

- Clear explanation
- Next action
- No technical stack traces in user UI

---

## 20. Upgrade Prompt Rules

Feature locks should be visible but not annoying.

Good locked message:

```text
PDF export is available on Pro and higher plans.
```

Bad locked message:

```text
You cannot use this.
```

Upgrade prompts should include:

- What is locked
- Which plan unlocks it
- Clear CTA
- No aggressive language

---

## 21. Translation-Ready UI

Default language is English.

Keep visible text translation-ready where practical.

Do not over-engineer early screens, but avoid scattering important business labels that should later be translations.

Important translatable areas:

- Navigation
- Buttons
- Form labels
- Error messages
- Plan names/descriptions
- Feature labels
- Barcode type names/descriptions
- FAQ
- Emails
- SEO text

---

## 22. Accessibility and Usability

Follow basic accessibility principles:

- Good contrast
- Focus states
- Keyboard-friendly forms
- Clear labels
- Semantic buttons/links
- Error messages tied to fields
- Do not rely only on color for status
- Responsive layouts

---

## 23. Responsive Rules

Desktop:

- Use full dashboard layout
- Two-column generator
- Sidebar navigation

Tablet:

- Reduce density
- Collapse secondary panels if needed

Mobile:

- Stack content
- Use bottom/sticky actions where useful
- Keep generator easy to use
- Avoid horizontal table overflow where possible
- Use cards for history items if tables are too dense

---

## 24. Implementation Rules

When implementing UI:

1. Use reusable components.
2. Keep page files readable.
3. Avoid duplicating markup.
4. Use backend-provided data.
5. Do not fake business access.
6. Use locked states for unavailable features.
7. Keep premium style consistent.
8. Do not create huge unrelated UI rewrites.
9. Keep forms connected to real backend endpoints.
10. Use clear names for components.

---

## 25. Strong UI Warnings

Do not:

- Build a cheap utility-site UI.
- Use random colors.
- Create fake dashboards disconnected from data.
- Hide plan limits.
- Hide upgrade opportunities.
- Use frontend-only security.
- Hard-code plan features in React.
- Make admin screens overly decorative.
- Make generator screen confusing.
- Build all screens in one giant component.
