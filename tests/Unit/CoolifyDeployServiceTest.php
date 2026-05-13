<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Deployment\CoolifyDeployService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoolifyDeployServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_coolify_env_rejects_safely(): void
    {
        $this->seed();

        config([
            'barcodeos.coolify.enabled' => true,
            'barcodeos.coolify.webhook_url' => null,
            'barcodeos.coolify.api_token' => null,
            'barcodeos.coolify.webhook_secret' => null,
        ]);

        $service = app(CoolifyDeployService::class);
        $user = User::factory()->create();

        $result = $service->triggerDeployment($user);

        $this->assertFalse($result['success']);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'system.update.rejected',
            'subject_type' => 'system_update',
        ]);
        $this->assertSame(1, AuditLog::query()->count());
    }
}
