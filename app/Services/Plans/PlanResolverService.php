<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

class PlanResolverService
{
    public function currentPlanFor(User $user): ?Plan
    {
        $subscription = Subscription::query()
            ->with('plan')
            ->whereBelongsTo($user)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->where(function ($query): void {
                $query
                    ->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>', now());
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>=', now());
            })
            ->orderByDesc('current_period_ends_at')
            ->orderByDesc('id')
            ->first();

        return $subscription?->plan ?? $this->defaultFreePlan();
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
}
