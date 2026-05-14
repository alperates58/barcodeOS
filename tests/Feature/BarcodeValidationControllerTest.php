<?php

namespace Tests\Feature;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->postJson('/app/barcodes/validate', [])
            ->assertStatus(401);
    }

    public function test_valid_request_returns_valid_true(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/validate', [
            'barcode_type_slug' => 'qr-code',
            'data' => 'https://barcodeos.com',
            'format' => 'png',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('normalized.format', 'png')
            ->assertJsonPath('normalized.data', 'https://barcodeos.com');
    }

    public function test_invalid_request_returns_structured_validation_errors(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/validate', [
            'barcode_type_slug' => 'qr-code',
            'data' => '',
            'format' => 'png',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('errors.0.code', 'data_required')
            ->assertJsonPath('errors.0.field', 'data');
    }

    public function test_locked_barcode_type_returns_access_error(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/validate', [
            'barcode_type_slug' => 'gs1-datamatrix',
            'data' => '(01)12345678901234(21)ABC123(93)XYZ',
            'format' => 'png',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('errors.0.code', 'barcode_type_not_allowed')
            ->assertJsonPath('errors.0.meta.missing_features.0', 'gs1.advanced');
    }

    public function test_locked_export_format_returns_access_error(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $barcodeType = \App\Models\BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();
        $barcodeType->update([
            'supported_export_formats' => ['png', 'pdf'],
            'default_format' => 'png',
        ]);

        $this->actingAs($user)
            ->postJson('/app/barcodes/validate', [
                'barcode_type_slug' => 'qr-code',
                'data' => 'https://barcodeos.com',
                'format' => 'pdf',
                'parameters' => [],
            ])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('errors.0.code', 'export_format_not_allowed')
            ->assertJsonPath('errors.0.field', 'format');
    }

    public function test_validate_endpoint_does_not_create_records_or_increment_usage(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/app/barcodes/validate', [
            'barcode_type_slug' => 'qr-code',
            'data' => 'https://barcodeos.com',
            'format' => 'png',
            'parameters' => [],
        ])->assertOk();

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }
}
