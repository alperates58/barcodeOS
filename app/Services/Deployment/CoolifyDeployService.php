<?php

namespace App\Services\Deployment;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Throwable;

class CoolifyDeployService
{
    public function configurationStatus(): array
    {
        return [
            'enabled' => (bool) config('barcodeos.coolify.enabled', false),
            'webhook_url' => config('barcodeos.coolify.webhook_url'),
            'webhook_configured' => filled(config('barcodeos.coolify.webhook_url')),
            'webhook_secret_configured' => filled(config('barcodeos.coolify.webhook_secret')),
            'api_token_configured' => filled(config('barcodeos.coolify.api_token')),
            'branch' => config('barcodeos.coolify.deploy_branch', 'main'),
            'method' => strtoupper((string) config('barcodeos.coolify.deploy_method', 'POST')),
            'timeout' => (int) config('barcodeos.coolify.timeout', 15),
        ];
    }

    public function triggerDeployment(?User $actor = null): array
    {
        $configuration = $this->configurationStatus();

        if (! $configuration['enabled']) {
            return $this->reject(
                $actor,
                'Coolify deploy integration is disabled. Set COOLIFY_ENABLED=true to use this action.',
                $configuration,
            );
        }

        if (! $configuration['webhook_configured']) {
            return $this->reject(
                $actor,
                'Coolify deploy webhook is not configured. Set COOLIFY_WEBHOOK_URL before triggering updates.',
                $configuration,
            );
        }

        try {
            $request = Http::acceptJson()
                ->asJson()
                ->timeout($configuration['timeout']);

            if (config('barcodeos.coolify.api_token')) {
                $request = $request->withToken((string) config('barcodeos.coolify.api_token'));
            }

            if (config('barcodeos.coolify.webhook_secret')) {
                $request = $request->withHeaders([
                    'X-BarcodeOS-Webhook-Secret' => (string) config('barcodeos.coolify.webhook_secret'),
                ]);
            }

            $payload = [
                'branch' => $configuration['branch'],
                'source' => 'barcodeos-admin',
                'requested_at' => now()->toIso8601String(),
            ];

            $response = match ($configuration['method']) {
                'GET' => $request->get((string) $configuration['webhook_url'], $payload),
                default => $request->send($configuration['method'], (string) $configuration['webhook_url'], [
                    'json' => $payload,
                ]),
            };

            if (! $response->successful()) {
                $message = "Coolify deployment trigger failed with HTTP {$response->status()}.";

                $this->log($actor, 'system.update.failed', $message, $configuration + [
                    'http_status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => $message,
                    'status_code' => $response->status(),
                ];
            }

            $message = 'Coolify deployment trigger accepted successfully.';

            $this->log($actor, 'system.update.triggered', $message, $configuration + [
                'http_status' => $response->status(),
            ]);

            return [
                'success' => true,
                'message' => $message,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $exception) {
            $message = 'Coolify deployment trigger could not be reached.';

            $this->log($actor, 'system.update.failed', $message, $configuration + [
                'exception' => $exception::class,
            ]);

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }

    protected function reject(?User $actor, string $message, array $configuration): array
    {
        $this->log($actor, 'system.update.rejected', $message, $configuration);

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    protected function log(?User $actor, string $action, string $message, array $configuration): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => 'system_update',
            'subject_id' => null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'old_values' => null,
            'new_values' => null,
            'metadata' => [
                'message' => $message,
                'coolify_enabled' => (bool) ($configuration['enabled'] ?? false),
                'webhook_configured' => (bool) ($configuration['webhook_configured'] ?? false),
                'webhook_secret_configured' => (bool) ($configuration['webhook_secret_configured'] ?? false),
                'api_token_configured' => (bool) ($configuration['api_token_configured'] ?? false),
                'branch' => $configuration['branch'] ?? 'main',
                'method' => $configuration['method'] ?? 'POST',
                'http_status' => $configuration['http_status'] ?? null,
                'exception' => $configuration['exception'] ?? null,
            ],
        ]);
    }
}
