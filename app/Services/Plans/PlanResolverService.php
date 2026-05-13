<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class PlanResolverService
{
    public function currentPlanFor(User $user): ?Plan
    {
        return $this->activeSubscriptionFor($user)?->plan ?? $this->defaultFreePlan();
    }

    public function defaultFreePlan(): ?Plan
    {
        $freePlanSlug = config('barcodeos.plans.default_free_slug', 'free');

        return Plan::query()
            ->where('slug', $freePlanSlug)
            ->where('is_active', true)
            ->first()
            ?? Plan::query()
                ->where('is_public', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();
    }

    public function activeSubscriptionFor(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->with('plan')
            ->paidPlan()
            ->withinCurrentPeriod()
            ->orderByDesc('current_period_ends_at')
            ->orderByDesc('id')
            ->first();
    }
}
