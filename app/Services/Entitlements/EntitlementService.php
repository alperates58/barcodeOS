<?php

namespace App\Services\Entitlements;

use App\Models\User;
use App\Services\Plans\PlanResolverService;

class EntitlementService
{
    public function __construct(
        protected PlanResolverService $planResolverService,
    ) {}

    public function allows(User $user, string $featureKey): bool
    {
        $feature = $this->featuresFor($user)[$featureKey] ?? null;

        if (! $feature || ! $feature['enabled']) {
            return false;
        }

        if (filled($feature['limit_value'])) {
            return (int) $feature['limit_value'] > 0;
        }

        return true;
    }

    public function cannot(User $user, string $featureKey): bool
    {
        return ! $this->allows($user, $featureKey);
    }

    public function value(User $user, string $featureKey, mixed $default = null): mixed
    {
        $feature = $this->featuresFor($user)[$featureKey] ?? null;

        if (! $feature || ! $feature['enabled']) {
            return $default;
        }

        if (filled($feature['limit_value'])) {
            return $feature['limit_value'];
        }

        if (filled($feature['value'])) {
            return $feature['value'];
        }

        return true;
    }

    public function featuresFor(User $user): array
    {
        $plan = $this->planResolverService->currentPlanFor($user);

        if (! $plan) {
            return [];
        }

        return $plan->planFeatures()
            ->with('feature')
            ->where('enabled', true)
            ->get()
            ->filter(fn ($planFeature) => $planFeature->feature?->is_active)
            ->mapWithKeys(function ($planFeature): array {
                $feature = $planFeature->feature;

                return [
                    $feature->key => [
                        'enabled' => (bool) $planFeature->enabled,
                        'value' => $planFeature->value,
                        'limit_value' => $planFeature->limit_value,
                        'name' => $feature->name,
                        'category' => $feature->category,
                        'value_type' => $feature->value_type,
                        'metadata' => $planFeature->metadata ?? [],
                    ],
                ];
            })
            ->all();
    }
}
