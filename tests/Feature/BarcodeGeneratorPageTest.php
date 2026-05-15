<?php

namespace Tests\Feature;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BarcodeGeneratorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/app/barcodes/generator')
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_generator_page(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->get('/app/barcodes/generator')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('barcodes/generator')
                ->where('defaultBarcodeTypeSlug', 'qr-code')
                ->where('routes.preview', route('app.barcodes.preview'))
                ->where('routes.validate', route('app.barcodes.validate'))
                ->where('routes.config', route('app.barcodes.types.config', ['barcodeType' => '__BARCODE_TYPE__']))
                ->where('usageSummary.daily_generation_limit.limit', 10)
                ->where('usageSummary.monthly_generation_limit.limit', 300)
                ->has('barcodeCategories')
                ->has('barcodeTypes', 8)
                ->where('barcodeTypes.0.slug', 'qr-code'));
    }

    public function test_generator_page_does_not_create_usage_or_generation_records(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->get('/app/barcodes/generator')->assertOk();

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }
}
