<?php

namespace App\Services\Entitlements;

use App\Models\User;
use App\Services\Plans\PlanResolverService;
use JsonException;

class EntitlementService
{
    public function __construct(
        protected PlanResolverService $planResolverService,
    ) {}

    public function allows(User $user, string $featureKey): bool
    {
        $feature = $this->featuresFor($user)[$featureKey] ?? null;

        if (! $feature) {
            return false;
        }

        return $feature['enabled'] && $feature['is_active'];
    }

    public function cannot(User $user, string $featureKey): bool
    {
        return ! $this->allows($user, $featureKey);
    }

    public function value(User $user, string $featureKey, mixed $default = null): mixed
    {
        $feature = $this->featuresFor($user)[$featureKey] ?? null;

        if (! $feature || ! $feature['enabled'] || ! $feature['is_active']) {
            return $default;
        }

        if ($feature['limit_value'] !== null) {
            return (int) $feature['limit_value'];
        }

        return $this->castFeatureValue(
            valueType: $feature['value_type'],
            value: $feature['value'],
            default: $default,
        );
    }

    public function featuresFor(User $user): array
    {
        $plan = $this->planResolverService->currentPlanFor($user);

        if (! $plan) {
            return [];
        }

        return $plan->planFeatures()
            ->with('feature')
            ->get()
            ->filter(fn ($planFeature) => $planFeature->feature !== null)
            ->mapWithKeys(function ($planFeature): array {
                $feature = $planFeature->feature;

                return [
                    $feature->key => [
                        'enabled' => (bool) $planFeature->enabled,
                        'is_active' => (bool) $feature->is_active,
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

    protected function castFeatureValue(string $valueType, mixed $value, mixed $default): mixed
    {
        return match ($valueType) {
            'boolean' => $this->castBooleanFeatureValue($value, $default),
            'integer' => $this->castIntegerFeatureValue($value, $default),
            'string' => $value !== null ? (string) $value : $default,
            'json' => $this->castJsonFeatureValue($value, $default),
            default => $value ?? $default,
        };
    }

    protected function castBooleanFeatureValue(mixed $value, mixed $default): mixed
    {
        if ($value === null) {
            return true;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $default;
        }

        return (bool) $value;
    }

    protected function castIntegerFeatureValue(mixed $value, mixed $default): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    protected function castJsonFeatureValue(mixed $value, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $default;
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $default;
        }
    }
}
