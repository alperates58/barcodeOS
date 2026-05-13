<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function __invoke(): Response
    {
        $plans = Plan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->with(['planFeatures.feature' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('pricing', [
            'plans' => $plans->map(function (Plan $plan): array {
                $enabledFeatures = $plan->planFeatures
                    ->where('enabled', true)
                    ->filter(fn ($planFeature) => $planFeature->feature)
                    ->values();

                return [
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'monthly_price' => $plan->monthly_price,
                    'yearly_price' => $plan->yearly_price,
                    'currency' => $plan->currency,
                    'trial_days' => $plan->trial_days,
                    'is_recommended' => (bool) data_get($plan->metadata, 'recommended', false),
                    'cta_label' => data_get($plan->metadata, 'cta_label', $plan->slug === 'enterprise' ? 'View Plan' : 'Get Started'),
                    'highlights' => $enabledFeatures
                        ->take(6)
                        ->map(function ($planFeature): array {
                            return [
                                'key' => $planFeature->feature->key,
                                'name' => $planFeature->feature->name,
                                'limit_value' => $planFeature->limit_value,
                                'value' => $planFeature->value,
                                'value_type' => $planFeature->feature->value_type,
                            ];
                        })
                        ->values(),
                ];
            })->values(),
        ]);
    }
}
