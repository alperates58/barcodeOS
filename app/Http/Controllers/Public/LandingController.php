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
    public function __invoke(): Response
    {
        $plans = Plan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeTypes = BarcodeType::query()
            ->where('status', 'active')
            ->with('category')
            ->orderBy('sort_order')
            ->get();

        $categories = BarcodeCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('welcome', [
            'stats' => [
                'plan_count' => $plans->count(),
                'barcode_type_count' => $activeTypes->count(),
                'category_count' => $categories->count(),
                'language_count' => Language::query()->where('is_active', true)->count(),
            ],
            'featuredTypes' => $activeTypes
                ->take(7)
                ->map(fn (BarcodeType $barcodeType): array => [
                    'name' => $barcodeType->name,
                    'slug' => $barcodeType->slug,
                    'category' => $barcodeType->category?->name,
                    'default_format' => strtoupper($barcodeType->default_format),
                    'description' => $barcodeType->description,
                ])
                ->values(),
            'categories' => $categories
                ->take(6)
                ->map(fn (BarcodeCategory $category): array => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ])
                ->values(),
            'plansPreview' => $plans
                ->take(3)
                ->map(fn (Plan $plan): array => [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'monthly_price' => $plan->monthly_price,
                    'currency' => $plan->currency,
                ])
                ->values(),
        ]);
    }
}
