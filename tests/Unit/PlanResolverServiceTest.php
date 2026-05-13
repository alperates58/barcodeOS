<?php

namespace Tests\Unit;

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
}
