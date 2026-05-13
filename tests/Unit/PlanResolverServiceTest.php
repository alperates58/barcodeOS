<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Plans\PlanResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_free_plan_can_be_resolved(): void
    {
        $this->seed();

        $service = app(PlanResolverService::class);
        $user = User::factory()->create();

        $this->assertSame('free', $service->defaultFreePlan()?->slug);
        $this->assertSame('free', $service->currentPlanFor($user)?->slug);
    }

    public function test_active_subscription_resolves_to_subscribed_plan(): void
    {
        $this->seed();

        $service = app(PlanResolverService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->assertSame($subscription->id, $service->activeSubscriptionFor($user)?->id);
        $this->assertSame('business', $service->currentPlanFor($user)?->slug);
    }

    public function test_trialing_subscription_resolves_to_subscribed_plan(): void
    {
        $this->seed();

        $service = app(PlanResolverService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addDays(14),
        ]);

        $this->assertSame('starter', $service->currentPlanFor($user)?->slug);
    }

    public function test_non_paid_or_invalid_statuses_fall_back_to_free_plan(): void
    {
        $this->seed();

        $service = app(PlanResolverService::class);
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        foreach (['past_due', 'unpaid', 'canceled', 'expired', 'incomplete', 'incomplete_expired'] as $status) {
            $user = User::factory()->create();

            Subscription::query()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'current_period_starts_at' => now()->subDay(),
                'current_period_ends_at' => now()->addMonth(),
            ]);

            $this->assertSame('free', $service->currentPlanFor($user)?->slug);
            $this->assertNull($service->activeSubscriptionFor($user));
        }
    }

    public function test_expired_active_subscription_falls_back_to_free_plan(): void
    {
        $this->seed();

        $service = app(PlanResolverService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subMonths(2),
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->assertSame('free', $service->currentPlanFor($user)?->slug);
    }
}
