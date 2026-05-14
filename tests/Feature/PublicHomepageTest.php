<?php

namespace Tests\Feature;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_homepage(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->where('generatorPanel.default_barcode_type_slug', 'qr-code'));
    }

    public function test_homepage_response_is_successful_and_includes_expected_props(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('heroStats')
                ->where('heroStats.plan_count', 5)
                ->where('heroStats.barcode_type_count', 8)
                ->where('heroStats.category_count', 5)
                ->has('barcodeCategories', 5)
                ->has('barcodeTypes', 8)
                ->has('featuredBarcodeTypes', 6)
                ->where('barcodeTypes.0.slug', 'qr-code')
                ->where('barcodeCategories.0.slug', 'linear-1d')
                ->where('generatorPanel.default_barcode_type_slug', 'qr-code')
                ->where('generatorPanel.selected_type.slug', 'qr-code')
                ->where('generatorPanel.export_formats.0', 'png')
                ->where('plansPreview.0.slug', 'free'));
    }

    public function test_homepage_does_not_create_usage_or_generation_records(): void
    {
        $this->seed();

        $this->get('/')->assertOk();

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_authenticated_user_can_open_homepage(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->where('generatorPanel.selected_type.slug', 'qr-code'));
    }
}
