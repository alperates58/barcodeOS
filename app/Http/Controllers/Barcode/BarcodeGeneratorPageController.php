<?php

namespace App\Http\Controllers\Barcode;

use App\Http\Controllers\Controller;
use App\Models\BarcodeCategory;
use App\Models\BarcodeType;
use App\Services\Plans\PlanResolverService;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarcodeGeneratorPageController extends Controller
{
    protected const ACTIVE_STATUSES = [
        'active',
        'enabled',
        'beta',
    ];

    public function __invoke(
        Request $request,
        PlanResolverService $planResolverService,
        UsageLimitService $usageLimitService,
    ): Response {
        $user = $request->user();
        $plan = $planResolverService->currentPlanFor($user);

        $barcodeTypes = BarcodeType::query()
            ->with('category')
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = BarcodeCategory::query()
            ->where('is_active', true)
            ->whereHas('barcodeTypes', fn ($query) => $query->whereIn('status', self::ACTIVE_STATUSES))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (BarcodeCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ])
            ->values();

        $barcodeTypeSummaries = $barcodeTypes
            ->map(fn (BarcodeType $barcodeType): array => [
                'id' => $barcodeType->id,
                'name' => $barcodeType->name,
                'slug' => $barcodeType->slug,
                'description' => $barcodeType->description,
                'example_value' => $barcodeType->example_value,
                'category' => $barcodeType->category ? [
                    'id' => $barcodeType->category->id,
                    'name' => $barcodeType->category->name,
                    'slug' => $barcodeType->category->slug,
                ] : null,
            ])
            ->values();

        return Inertia::render('barcodes/generator', [
            'barcodeCategories' => $categories,
            'barcodeTypes' => $barcodeTypeSummaries,
            'defaultBarcodeTypeSlug' => $barcodeTypeSummaries->first()['slug'] ?? null,
            'currentPlan' => $plan ? [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'trial_days' => $plan->trial_days,
                'currency' => $plan->currency,
            ] : null,
            'usageSummary' => $usageLimitService->summary($user),
            'routes' => [
                'config' => route('app.barcodes.types.config', ['barcodeType' => '__BARCODE_TYPE__']),
                'validate' => route('app.barcodes.validate'),
            ],
        ]);
    }
}
