<?php

namespace Tests\Unit;

use App\Models\UsageCounter;
use App\Models\User;
use App\Services\Usage\UsageLimitService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_usage_checks_do_not_create_counters(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-13 10:00:00'));

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        $this->assertTrue($service->canUse($user, 'daily_generation_limit'));
        $this->assertSame(10, $service->remaining($user, 'daily_generation_limit'));
        $this->assertSame(0, UsageCounter::query()->count());
    }

    public function test_can_use_returns_false_when_limit_is_reached(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-13 10:00:00'));

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        UsageCounter::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'feature_key' => 'daily_generation_limit',
            'period_type' => 'daily',
            'period_start' => CarbonImmutable::parse('2026-05-13 00:00:00'),
            'period_end' => CarbonImmutable::parse('2026-05-13 23:59:59'),
            'used' => 10,
            'limit' => 10,
            'source' => 'web',
            'metadata' => [],
        ]);

        $this->assertFalse($service->canUse($user, 'daily_generation_limit'));
        $this->assertSame(0, $service->remaining($user, 'daily_generation_limit'));
    }

    public function test_increment_creates_daily_and_monthly_counters(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-13 10:00:00'));

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        $service->increment($user, 'barcode.generate');

        $dailyCounter = UsageCounter::query()->where('feature_key', 'daily_generation_limit')->first();
        $monthlyCounter = UsageCounter::query()->where('feature_key', 'monthly_generation_limit')->first();

        $this->assertNotNull($dailyCounter);
        $this->assertNotNull($monthlyCounter);
        $this->assertSame(1, $dailyCounter?->used);
        $this->assertSame(1, $monthlyCounter?->used);
        $this->assertSame(2, UsageCounter::query()->count());
    }

    public function test_increment_updates_existing_periods_without_creating_duplicates(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-13 10:00:00'));

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        $service->increment($user, 'barcode.generate');
        $service->increment($user, 'barcode.generate');

        $dailyCounter = UsageCounter::query()->where('feature_key', 'daily_generation_limit')->firstOrFail();
        $monthlyCounter = UsageCounter::query()->where('feature_key', 'monthly_generation_limit')->firstOrFail();

        $this->assertSame(2, $dailyCounter->used);
        $this->assertSame(2, $monthlyCounter->used);
        $this->assertSame(2, UsageCounter::query()->count());
    }

    public function test_summary_returns_correct_remaining_values(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-13 10:00:00'));

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        $service->increment($user, 'barcode.generate', amount: 3);

        $summary = $service->summary($user);

        $this->assertSame(10, $summary['daily_generation_limit']['limit']);
        $this->assertSame(3, $summary['daily_generation_limit']['used']);
        $this->assertSame(7, $summary['daily_generation_limit']['remaining']);
        $this->assertSame(300, $summary['monthly_generation_limit']['limit']);
        $this->assertSame(3, $summary['monthly_generation_limit']['used']);
        $this->assertSame(297, $summary['monthly_generation_limit']['remaining']);
    }
}
