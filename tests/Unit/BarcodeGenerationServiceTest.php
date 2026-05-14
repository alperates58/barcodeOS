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

        $result = $service->generate($user, $barcodeType, '   ', 'png');

        $this->assertFalse($result['success']);
        $this->assertSame('validation_failed', $result['status']);
        $this->assertFalse($result['validation']['valid']);
        $this->assertSame('data_required', $result['error']['code']);
        $this->assertSame([], $result['normalized']['parameters']);
    }

    public function test_valid_request_with_unsupported_renderer_returns_renderer_not_supported(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'data-matrix')->firstOrFail();

        $result = $service->generate($user, $barcodeType, 'DMX-42-ALPHA', 'png');

        $this->assertFalse($result['success']);
        $this->assertSame('renderer_not_supported', $result['status']);
        $this->assertTrue($result['validation']['valid']);
        $this->assertSame('renderer_not_supported', $result['error']['code']);
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

        $result = $service->generate($user, $barcodeType, '  HELLO-42  ', ' PNG ', [
            'width' => '420',
            'ignored' => 'value',
        ]);

        $this->assertSame($result['validation']['normalized'], $result['normalized']);
        $this->assertSame('HELLO-42', $result['normalized']['data']);
        $this->assertSame('png', $result['normalized']['format']);
        $this->assertSame(['width' => 420], $result['normalized']['parameters']);
    }

    public function test_generation_service_does_not_create_usage_counters(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'png');

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

        $service->generate($user, $barcodeType, 'HELLO-42', 'png');

        $counter = UsageCounter::query()->where('feature_key', 'daily_generation_limit')->firstOrFail();

        $this->assertSame(2, $counter->used);
    }

    public function test_generation_service_does_not_create_generated_barcodes(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'png');

        $this->assertSame(0, GeneratedBarcode::query()->count());
    }

    public function test_generation_service_does_not_create_barcode_exports(): void
    {
        $this->seed();

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->generate($user, $barcodeType, 'HELLO-42', 'png');

        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_renderer_implementation_is_not_called_even_when_registry_reports_support(): void
    {
        $this->seed();

        FakeTestBarcodeRenderer::$wasCalled = false;

        $registry = $this->mock(BarcodeTypeRegistry::class);
        $registry->shouldReceive('hasRendererFor')
            ->once()
            ->with('fake-supported')
            ->andReturn(true);

        $service = app(BarcodeGenerationService::class);
        $user = User::factory()->create();
        $barcodeType = $this->makeBarcodeType([
            'slug' => 'fake-supported',
        ]);

        $result = $service->generate($user, $barcodeType, 'HELLO-42', 'png');

        $this->assertTrue($result['success']);
        $this->assertSame('ready_for_render', $result['status']);
        $this->assertNull($result['error']);
        $this->assertFalse(FakeTestBarcodeRenderer::$wasCalled);
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

class FakeTestBarcodeRenderer implements BarcodeRendererInterface
{
    public static bool $wasCalled = false;

    public function render(string $data, array $parameters = []): mixed
    {
        self::$wasCalled = true;

        return null;
    }
}
