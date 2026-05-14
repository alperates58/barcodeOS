<?php

namespace Tests\Unit;

use App\Models\BarcodeCategory;
use App\Models\BarcodeType;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Barcode\BarcodeAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_use_basic_barcode_type_with_base_generation_feature(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->firstOrFail();

        $barcodeType->update(['required_features' => ['barcode.generate']]);

        $this->assertTrue($service->canUseBarcodeType($user, $barcodeType->fresh()));
        $this->assertSame([], $service->missingBarcodeTypeFeatures($user, $barcodeType->fresh()));
    }

    public function test_user_without_required_feature_cannot_use_restricted_barcode_type(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'pdf417')->firstOrFail();

        $this->assertFalse($service->canUseBarcodeType($user, $barcodeType));
        $this->assertSame(['barcode.logistics_types'], $service->missingBarcodeTypeFeatures($user, $barcodeType));
    }

    public function test_paid_user_with_required_feature_can_use_restricted_barcode_type(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $barcodeType = BarcodeType::query()->where('slug', 'pdf417')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($service->canUseBarcodeType($user, $barcodeType));
        $this->assertSame([], $service->missingBarcodeTypeFeatures($user, $barcodeType));
    }

    public function test_free_user_can_export_png_when_enabled(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();

        $this->assertTrue($service->canExportFormat($user, 'png'));
    }

    public function test_free_user_cannot_export_pdf_when_disabled(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();

        $this->assertFalse($service->canExportFormat($user, 'pdf'));
    }

    public function test_pro_user_can_export_pdf_when_enabled(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($service->canExportFormat($user, 'pdf'));
    }

    public function test_unknown_export_format_returns_false(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();

        $this->assertNull($service->exportFeatureKey('docx'));
        $this->assertFalse($service->canExportFormat($user, 'docx'));
    }

    public function test_barcode_type_with_multiple_required_features_requires_all_features(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $category = BarcodeCategory::query()->firstOrFail();

        $barcodeType = BarcodeType::query()->create([
            'barcode_category_id' => $category->id,
            'name' => 'GS1 Restricted',
            'slug' => 'gs1-restricted',
            'status' => 'active',
            'validation_rules' => [],
            'supported_export_formats' => ['png'],
            'required_features' => ['barcode.retail_types', 'gs1.advanced'],
            'parameter_schema' => [],
            'metadata' => [],
        ]);

        $this->assertFalse($service->canUseBarcodeType($user, $barcodeType));
        $this->assertSame(['gs1.advanced'], $service->missingBarcodeTypeFeatures($user, $barcodeType));
    }

    public function test_empty_required_features_still_require_barcode_generate(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->firstOrFail();

        $barcodeType->update(['required_features' => []]);

        $this->assertTrue($service->canUseBarcodeType($user, $barcodeType->fresh()));
    }

    public function test_user_without_gs1_advanced_cannot_use_seeded_gs1_datamatrix(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'gs1-datamatrix')->firstOrFail();

        $this->assertFalse($service->canUseBarcodeType($user, $barcodeType));
        $this->assertSame(['gs1.advanced'], $service->missingBarcodeTypeFeatures($user, $barcodeType));
    }

    public function test_user_with_gs1_advanced_can_use_seeded_gs1_datamatrix(): void
    {
        $this->seed();

        $service = app(BarcodeAccessService::class);
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $barcodeType = BarcodeType::query()->where('slug', 'gs1-datamatrix')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($service->canUseBarcodeType($user, $barcodeType));
        $this->assertSame([], $service->missingBarcodeTypeFeatures($user, $barcodeType));
    }
}
