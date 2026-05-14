<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BarcodeCategory;
use App\Models\BarcodeType;
use App\Models\Language;
use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    protected const ACTIVE_BARCODE_STATUSES = [
        'active',
        'enabled',
        'beta',
    ];

    public function __invoke(): Response
    {
        $plans = Plan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeTypes = BarcodeType::query()
            ->with('category')
            ->whereIn('status', self::ACTIVE_BARCODE_STATUSES)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = BarcodeCategory::query()
            ->where('is_active', true)
            ->whereHas('barcodeTypes', fn ($query) => $query->whereIn('status', self::ACTIVE_BARCODE_STATUSES))
            ->with([
                'barcodeTypes' => fn ($query) => $query
                    ->whereIn('status', self::ACTIVE_BARCODE_STATUSES)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $barcodeTypeSummaries = $activeTypes
            ->map(fn (BarcodeType $barcodeType): array => [
                'id' => $barcodeType->id,
                'name' => $barcodeType->name,
                'slug' => $barcodeType->slug,
                'description' => $barcodeType->description,
                'example_value' => $barcodeType->example_value,
                'default_format' => $barcodeType->default_format,
                'supported_export_formats' => $this->normalizeFormats($barcodeType->supported_export_formats),
                'required_features' => $this->normalizeFeatureKeys($barcodeType->required_features),
                'category' => $barcodeType->category ? [
                    'id' => $barcodeType->category->id,
                    'name' => $barcodeType->category->name,
                    'slug' => $barcodeType->category->slug,
                ] : null,
            ])
            ->values();

        $categorySummaries = $categories
            ->map(fn (BarcodeCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'types' => $category->barcodeTypes
                    ->map(fn (BarcodeType $barcodeType): array => [
                        'id' => $barcodeType->id,
                        'name' => $barcodeType->name,
                        'slug' => $barcodeType->slug,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values();

        $defaultBarcodeType = $activeTypes->first();
        $defaultCategory = $defaultBarcodeType?->category;

        return Inertia::render('welcome', [
            'heroStats' => [
                'plan_count' => $plans->count(),
                'barcode_type_count' => $activeTypes->count(),
                'category_count' => $categories->count(),
                'language_count' => Language::query()->where('is_active', true)->count(),
            ],
            'barcodeCategories' => $categorySummaries,
            'barcodeTypes' => $barcodeTypeSummaries,
            'featuredBarcodeTypes' => $activeTypes
                ->take(6)
                ->map(fn (BarcodeType $barcodeType): array => [
                    'id' => $barcodeType->id,
                    'name' => $barcodeType->name,
                    'slug' => $barcodeType->slug,
                    'description' => $barcodeType->description,
                    'default_format' => strtoupper((string) $barcodeType->default_format),
                    'supported_export_formats' => $this->normalizeFormats($barcodeType->supported_export_formats),
                    'category' => $barcodeType->category ? [
                        'id' => $barcodeType->category->id,
                        'name' => $barcodeType->category->name,
                        'slug' => $barcodeType->category->slug,
                    ] : null,
                ])
                ->values(),
            'plansPreview' => $plans
                ->take(4)
                ->map(fn (Plan $plan): array => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'currency' => $plan->currency,
                    'cta_label' => data_get($plan->metadata, 'cta_label', $plan->slug === 'enterprise' ? 'Contact sales' : 'View plan'),
                    'is_recommended' => (bool) data_get($plan->metadata, 'recommended', false),
                ])
                ->values(),
            'generatorPanel' => [
                'default_barcode_type_slug' => $defaultBarcodeType?->slug,
                'selected_type' => $defaultBarcodeType ? [
                    'id' => $defaultBarcodeType->id,
                    'name' => $defaultBarcodeType->name,
                    'slug' => $defaultBarcodeType->slug,
                    'description' => $defaultBarcodeType->description,
                    'example_value' => $defaultBarcodeType->example_value,
                    'default_format' => $defaultBarcodeType->default_format,
                    'supported_export_formats' => $this->normalizeFormats($defaultBarcodeType->supported_export_formats),
                    'required_features' => $this->normalizeFeatureKeys($defaultBarcodeType->required_features),
                    'category' => $defaultCategory ? [
                        'id' => $defaultCategory->id,
                        'name' => $defaultCategory->name,
                        'slug' => $defaultCategory->slug,
                    ] : null,
                ] : null,
                'categories' => $categorySummaries,
                'export_formats' => $defaultBarcodeType ? $this->normalizeFormats($defaultBarcodeType->supported_export_formats) : [],
                'status' => [
                    'eyebrow' => 'Read-only generator foundation',
                    'message' => 'Preview rendering, downloads and file generation remain disabled until Phase 4.',
                    'validate_message' => 'Sign in to validate barcode input inside the app. The public homepage does not call config or validate endpoints.',
                ],
            ],
            'teaserSections' => [
                'api' => [
                    'title' => 'API access planned',
                    'description' => 'Developer-facing barcode generation and API keys will arrive in a later phase after rendering and entitlement workflows are complete.',
                ],
                'bulk' => [
                    'title' => 'Bulk generation planned',
                    'description' => 'CSV and Excel driven queue workflows stay intentionally out of scope until the rendering engine is production-ready.',
                ],
                'faq' => [
                    [
                        'question' => 'Can I download barcode files from the homepage today?',
                        'answer' => 'No. This public page is a discovery layer only. Rendering, download and export flows are part of Phase 4 and later billing phases.',
                    ],
                    [
                        'question' => 'Does the homepage validate barcode content?',
                        'answer' => 'No. Validation is available only inside the authenticated app generator, and remains side-effect-free in this phase.',
                    ],
                    [
                        'question' => 'Are pricing and barcode types backed by real data?',
                        'answer' => 'Yes. Public barcode catalog and pricing teasers come from active database records managed from the admin foundation.',
                    ],
                ],
                'trust' => [
                    'Admin-managed barcode catalog',
                    'Entitlement-aware plan foundation',
                    'Secure deployment and audit-ready operations',
                    'Phase-based rollout without fake rendering claims',
                ],
            ],
        ]);
    }

    protected function normalizeFormats(mixed $formats): array
    {
        if (! is_array($formats)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                fn (mixed $format): string => is_string($format) ? strtolower(trim($format)) : '',
                $formats,
            ),
            fn (string $format): bool => $format !== '',
        )));
    }

    protected function normalizeFeatureKeys(mixed $featureKeys): array
    {
        if (! is_array($featureKeys)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(
                fn (mixed $featureKey): string => is_string($featureKey) ? trim($featureKey) : '',
                $featureKeys,
            ),
            fn (string $featureKey): bool => $featureKey !== '',
        )));
    }
}
