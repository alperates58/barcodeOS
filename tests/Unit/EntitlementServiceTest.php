<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_feature_returns_false(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();

        $this->assertFalse($service->allows($user, 'feature.that.does.not.exist'));
    }

    public function test_free_fallback_user_can_access_seeded_barcode_generation(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();

        $this->assertTrue($service->allows($user, 'barcode.generate'));
    }

    public function test_free_fallback_user_cannot_access_pdf_export_when_not_enabled(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();

        $this->assertFalse($service->allows($user, 'export.pdf'));
    }

    public function test_active_pro_subscription_can_access_pdf_export(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($service->allows($user, 'export.pdf'));
    }

    public function test_value_returns_configured_numeric_limit_as_integer(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();

        $value = $service->value($user, 'daily_generation_limit');

        $this->assertIsInt($value);
        $this->assertSame(10, $value);
    }

    public function test_inactive_feature_does_not_grant_access(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();

        Feature::query()->where('key', 'export.png')->update(['is_active' => false]);

        $this->assertFalse($service->allows($user, 'export.png'));
    }

    public function test_disabled_plan_feature_does_not_grant_access(): void
    {
        $this->seed();

        $service = app(EntitlementService::class);
        $user = User::factory()->create();
        $freePlan = Plan::query()->where('slug', 'free')->firstOrFail();

        $freePlan->planFeatures()
            ->whereHas('feature', fn ($query) => $query->where('key', 'barcode.generate'))
            ->update(['enabled' => false]);

        $this->assertFalse($service->allows($user, 'barcode.generate'));
    }
}
