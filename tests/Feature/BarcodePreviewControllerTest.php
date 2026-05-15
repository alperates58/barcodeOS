<?php

namespace Tests\Feature;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodePreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_rejected_by_auth_convention(): void
    {
        $this->postJson('/app/barcodes/preview', [])
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_preview_qr_code_svg(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/preview', [
            'barcode_type_slug' => 'qr-code',
            'data' => 'https://barcodeos.com',
            'format' => 'svg',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'rendered')
            ->assertJsonPath('rendered.format', 'svg')
            ->assertJsonPath('rendered.mime_type', 'image/svg+xml')
            ->assertJsonPath('rendered.metadata.renderer', 'qr-code');
    }

    public function test_invalid_request_returns_validation_failed(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/preview', [
            'barcode_type_slug' => 'qr-code',
            'data' => '',
            'format' => 'svg',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 'validation_failed')
            ->assertJsonPath('error.code', 'data_required');
    }

    public function test_unsupported_barcode_type_returns_renderer_not_supported(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/preview', [
            'barcode_type_slug' => 'data-matrix',
            'data' => 'DMX-42-ALPHA',
            'format' => 'svg',
            'parameters' => [],
        ])
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 'renderer_not_supported')
            ->assertJsonPath('error.code', 'renderer_not_supported');
    }

    public function test_preview_endpoint_has_no_side_effects(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->postJson('/app/barcodes/preview', [
            'barcode_type_slug' => 'qr-code',
            'data' => 'https://barcodeos.com',
            'format' => 'svg',
            'parameters' => [],
        ])->assertOk();

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }
}
