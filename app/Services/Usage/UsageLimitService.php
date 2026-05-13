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

        $counter = $this->findCounterFor($user, $limitKey, $source);

        return $counter?->used < $limit;
    }

    public function remaining(User $user, string $limitKey, string $source = 'web'): ?int
    {
        $limit = $this->limitFor($user, $limitKey);

        if ($limit === null) {
            return null;
        }

        $counter = $this->findCounterFor($user, $limitKey, $source);

        return max(0, $limit - (int) ($counter?->used ?? 0));
    }

    public function increment(User $user, string $featureKey, string $source = 'web', int $amount = 1): void
    {
        foreach ($this->usageKeysFor($featureKey, $source) as $limitKey) {
            $limit = $this->limitFor($user, $limitKey);
            $counter = $this->createOrUpdateCounterFor($user, $limitKey, $source, $limit);

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
            $counter = $this->findCounterFor($user, $limitKey, $source);

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

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function findCounterFor(User $user, string $limitKey, string $source): ?UsageCounter
    {
        $window = $this->resolveWindow($limitKey);

        if (! $window) {
            return null;
        }

        [$periodType, $periodStart, $periodEnd] = $window;

        return UsageCounter::query()
            ->where('user_id', $user->id)
            ->where('feature_key', $limitKey)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->where('source', $source)
            ->first();
    }

    protected function createOrUpdateCounterFor(User $user, string $limitKey, string $source, ?int $limit = null): UsageCounter
    {
        $window = $this->resolveWindow($limitKey);

        if (! $window) {
            throw new \InvalidArgumentException("Unsupported usage limit key [{$limitKey}].");
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
                'period_end' => $periodEnd,
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

        if ($limit === null && $counter->limit !== null) {
            $changes['limit'] = null;
        }

        if (! $counter->period_end?->equalTo($periodEnd)) {
            $changes['period_end'] = $periodEnd;
        }

        if ($changes !== []) {
            $counter->fill($changes)->save();
        }

        return $counter->refresh();
    }

    protected function resolveWindow(string $limitKey): ?array
    {
        $now = CarbonImmutable::now();

        return match ($limitKey) {
            'daily_generation_limit' => [
                'daily',
                $now->startOfDay(),
                $now->endOfDay(),
            ],
            'monthly_generation_limit', 'api_monthly_request_limit', 'bulk_monthly_job_limit' => [
                'monthly',
                $now->startOfMonth(),
                $now->endOfMonth(),
            ],
            default => null,
        };
    }
}
