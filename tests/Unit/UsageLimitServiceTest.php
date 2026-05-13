<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Usage\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_user_usage_check_does_not_crash(): void
    {
        $this->seed();

        $service = app(UsageLimitService::class);
        $user = User::factory()->create();

        $this->assertTrue($service->canUse($user, 'daily_generation_limit'));
        $this->assertSame(10, $service->remaining($user, 'daily_generation_limit'));

        $service->increment($user, 'barcode.generate');

        $summary = $service->summary($user);

        $this->assertSame(1, $summary['daily_generation_limit']['used']);
        $this->assertSame(9, $summary['daily_generation_limit']['remaining']);
    }
}
