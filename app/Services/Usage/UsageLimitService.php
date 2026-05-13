<?php

namespace App\Services\Usage;

use App\Models\UsageCounter;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use App\Services\Plans\PlanResolverService;
use Carbon\CarbonImmutable;

class UsageLimitService
{
    public function __construct(
        protected EntitlementService $entitlementService,
        protected PlanResolverService $planResolverService,
    ) {}

    public function canUse(User $user, string $limitKey, string $source = 'web'): bool
    {
        $limit = $this->limitFor($user, $limitKey);

        if ($limit === null) {
            return true;
        }

        $counter = $this->counterFor($user, $limitKey, $source, $limit);

        return $counter?->used < $limit;
    }

    public function remaining(User $user, string $limitKey, string $source = 'web'): ?int
    {
        $limit = $this->limitFor($user, $limitKey);

        if ($limit === null) {
            return null;
        }

        $counter = $this->counterFor($user, $limitKey, $source, $limit);

        return max(0, $limit - (int) ($counter?->used ?? 0));
    }

    public function increment(User $user, string $featureKey, string $source = 'web', int $amount = 1): void
    {
        foreach ($this->usageKeysFor($featureKey, $source) as $limitKey) {
            $limit = $this->limitFor($user, $limitKey);

            if ($limit === null) {
                continue;
            }

            $counter = $this->counterFor($user, $limitKey, $source, $limit);

            if (! $counter) {
                continue;
            }

            $counter->increment('used', $amount);
        }
    }

    public function summary(User $user): array
    {
        $summary = [];

        foreach ([
            'daily_generation_limit',
            'monthly_generation_limit',
            'api_monthly_request_limit',
            'bulk_monthly_job_limit',
        ] as $limitKey) {
            $source = match ($limitKey) {
                'api_monthly_request_limit' => 'api',
                'bulk_monthly_job_limit' => 'bulk',
                default => 'web',
            };

            $limit = $this->limitFor($user, $limitKey);
            $counter = $limit === null ? null : $this->counterFor($user, $limitKey, $source, $limit);

            $summary[$limitKey] = [
                'limit' => $limit,
                'used' => (int) ($counter?->used ?? 0),
                'remaining' => $limit === null ? null : max(0, $limit - (int) ($counter?->used ?? 0)),
                'period_type' => $counter?->period_type,
                'source' => $source,
            ];
        }

        return $summary;
    }

    protected function usageKeysFor(string $featureKey, string $source): array
    {
        if (in_array($featureKey, [
            'daily_generation_limit',
            'monthly_generation_limit',
            'api_monthly_request_limit',
            'bulk_monthly_job_limit',
        ], true)) {
            return [$featureKey];
        }

        return match (true) {
            $featureKey === 'barcode.generate' => ['daily_generation_limit', 'monthly_generation_limit'],
            $source === 'api' || $featureKey === 'api.access' => ['api_monthly_request_limit'],
            $source === 'bulk' || $featureKey === 'bulk.generate' => ['bulk_monthly_job_limit'],
            default => [],
        };
    }

    protected function limitFor(User $user, string $limitKey): ?int
    {
        $value = $this->entitlementService->value($user, $limitKey);

        if (blank($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function counterFor(User $user, string $limitKey, string $source, ?int $limit = null): ?UsageCounter
    {
        $window = $this->resolveWindow($limitKey);

        if (! $window) {
            return null;
        }

        [$periodType, $periodStart, $periodEnd] = $window;
        $plan = $this->planResolverService->currentPlanFor($user);

        $counter = UsageCounter::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'feature_key' => $limitKey,
                'period_type' => $periodType,
                'period_start' => $periodStart->toDateTimeString(),
                'source' => $source,
            ],
            [
                'plan_id' => $plan?->id,
                'period_end' => $periodEnd->toDateTimeString(),
                'used' => 0,
                'limit' => $limit,
                'metadata' => [],
            ],
        );

        $changes = [];

        if ($counter->plan_id !== $plan?->id) {
            $changes['plan_id'] = $plan?->id;
        }

        if ($limit !== null && $counter->limit !== $limit) {
            $changes['limit'] = $limit;
        }

        if ($counter->period_end?->toDateTimeString() !== $periodEnd->toDateTimeString()) {
            $changes['period_end'] = $periodEnd;
        }

        if ($changes !== []) {
            $counter->fill($changes)->save();
        }

        return $counter->refresh();
    }

    protected function resolveWindow(string $limitKey): ?array
    {
        return match ($limitKey) {
            'daily_generation_limit' => [
                'daily',
                CarbonImmutable::now()->startOfDay(),
                CarbonImmutable::now()->endOfDay(),
            ],
            'monthly_generation_limit', 'api_monthly_request_limit', 'bulk_monthly_job_limit' => [
                'monthly',
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
            ],
            default => null,
        };
    }
}
