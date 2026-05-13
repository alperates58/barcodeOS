<?php

namespace Tests\Unit;

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
}
