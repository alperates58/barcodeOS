<?php

namespace Tests\Feature;

use App\Models\BarcodeExport;
use App\Models\BarcodeParameter;
use App\Models\BarcodeType;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeTypeConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->seed();

        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $this->get("/app/barcodes/types/{$barcodeType->slug}/config")
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_fetch_barcode_type_config(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $barcodeType->update([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'label' => 'Width',
                    'type' => 'integer',
                    'default' => 320,
                    'required' => true,
                ],
            ],
            'supported_export_formats' => ['png', 'svg', 'pdf'],
            'default_format' => 'png',
        ]);

        BarcodeParameter::query()->create([
            'barcode_type_id' => $barcodeType->id,
            'label' => 'Foreground color',
            'key' => 'foreground_color',
            'type' => 'color',
            'default_value' => '#111111',
            'is_required' => false,
            'available_features' => ['branding.custom_colors'],
            'sort_order' => 10,
            'is_active' => true,
            'metadata' => [],
        ]);

        $this->actingAs($user)
            ->get("/app/barcodes/types/{$barcodeType->slug}/config")
            ->assertOk()
            ->assertJsonPath('slug', 'qr-code')
            ->assertJsonPath('access.can_use', true)
            ->assertJsonPath('parameter_schema.0.key', 'width')
            ->assertJsonPath('parameter_schema.1.key', 'foreground_color')
            ->assertJsonPath('export_formats.0.format', 'png')
            ->assertJsonPath('export_formats.0.allowed', true)
            ->assertJsonPath('export_formats.2.format', 'pdf')
            ->assertJsonPath('export_formats.2.allowed', false);

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_response_includes_missing_access_features_for_locked_barcode_type(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'gs1-datamatrix')->firstOrFail();

        $this->actingAs($user)
            ->get("/app/barcodes/types/{$barcodeType->slug}/config")
            ->assertOk()
            ->assertJsonPath('access.can_use', false)
            ->assertJsonPath('access.missing_features.0', 'gs1.advanced');
    }

    public function test_inactive_barcode_type_returns_not_found(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();
        $barcodeType->update(['status' => 'archived']);

        $this->actingAs($user)
            ->get("/app/barcodes/types/{$barcodeType->slug}/config")
            ->assertNotFound();
    }
}
