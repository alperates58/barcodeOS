<?php

namespace Tests\Unit;

use App\Models\BarcodeCategory;
use App\Models\BarcodeExport;
use App\Models\BarcodeType;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\Barcode\BarcodeGenerationService;
use App\Services\Barcode\BarcodeTypeRegistry;
use App\Services\Barcode\Renderers\Contracts\BarcodeRendererInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_request_returns_validation_failed(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $result = $service->generate($user, $barcodeType, '   ', 'svg');

        $this->assertFalse($result['success']);
        $this->assertSame('validation_failed', $result['status']);
        $this->assertFalse($result['validation']['valid']);
        $this->assertSame('data_required', $result['error']['code']);
        $this->assertSame([], $result['normalized']['parameters']);
        $this->assertNull($result['rendered']);
    }

    public function test_valid_request_with_unsupported_renderer_returns_renderer_not_supported(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'data-matrix')->firstOrFail();

        $result = $service->generate($user, $barcodeType, 'DMX-42-ALPHA', 'svg');

        $this->assertFalse($result['success']);
        $this->assertSame('renderer_not_supported', $result['status']);
        $this->assertTrue($result['validation']['valid']);
        $this->assertSame('renderer_not_supported', $result['error']['code']);
        $this->assertNull($result['rendered']);
    }

    public function test_normalized_payload_is_preserved(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'default' => 300,
                    'min' => 100,
                    'max' => 600,
                ],
            ],
        ]);

        $result = $service->generate($user, $barcodeType, '  HELLO-42  ', ' SVG ', [
            'width' => '420',
            'ignored' => 'value',
        ]);

        $this->assertSame($result['validation']['normalized'], $result['normalized']);
        $this->assertSame('HELLO-42', $result['normalized']['data']);
        $this->assertSame('svg', $result['normalized']['format']);
        $this->assertSame(['width' => 420], $result['normalized']['parameters']);
    }

    public function test_valid_qr_svg_request_returns_rendered_payload(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $result = $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $this->assertTrue($result['success']);
        $this->assertSame('rendered', $result['status']);
        $this->assertSame('svg', $result['rendered']['format']);
        $this->assertSame('image/svg+xml', $result['rendered']['mime_type']);
        $this->assertStringContainsString('<svg', $result['rendered']['content']);
    }

    public function test_generation_service_returns_renderer_not_supported_for_code_128(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'code-128')->firstOrFail();

        $result = $service->generate($user, $barcodeType, 'CODE128-42', 'svg');

        $this->assertFalse($result['success']);
        $this->assertSame('renderer_not_supported', $result['status']);
        $this->assertNull($result['rendered']);
    }

    public function test_generation_service_does_not_create_usage_counters(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $this->assertSame(0, UsageCounter::query()->count());
    }

    public function test_generation_service_does_not_increment_existing_usage_counters(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-14 10:00:00'));

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        UsageCounter::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'feature_key' => 'daily_generation_limit',
            'period_type' => 'daily',
            'period_start' => CarbonImmutable::parse('2026-05-14 00:00:00'),
            'period_end' => CarbonImmutable::parse('2026-05-14 23:59:59'),
            'used' => 2,
            'limit' => 10,
            'source' => 'web',
            'metadata' => [],
        ]);

        $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $counter = UsageCounter::query()->where('feature_key', 'daily_generation_limit')->firstOrFail();

        $this->assertSame(2, $counter->used);
    }

    public function test_generation_service_does_not_create_generated_barcodes(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $this->assertSame(0, GeneratedBarcode::query()->count());
    }

    public function test_generation_service_does_not_create_barcode_exports(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_render_success_does_not_create_persistence_records(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $result = $service->generate($user, $barcodeType, 'HELLO-42', 'svg');

        $this->assertTrue($result['success']);
        $this->assertSame('rendered', $result['status']);
        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    protected function makeBarcodeType(array $overrides = []): BarcodeType
    {
        $category = BarcodeCategory::query()->firstOrFail();

        return BarcodeType::query()->create(array_merge([
            'barcode_category_id' => $category->id,
            'name' => 'Test Barcode '.uniqid(),
            'slug' => 'test-barcode-'.uniqid(),
            'status' => 'active',
            'validation_rules' => [],
            'default_format' => 'png',
            'supported_export_formats' => ['png'],
            'required_features' => ['barcode.generate'],
            'parameter_schema' => [],
            'metadata' => [],
        ], $overrides));
    }
}
