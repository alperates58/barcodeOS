<?php

namespace Tests\Feature;

use App\Models\BarcodeCategory;
use App\Models\BarcodeType;
use App\Models\Feature;
use App\Models\Language;
use App\Models\PaymentProvider;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_seeders_are_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, Language::query()->count());
        $this->assertSame(5, Plan::query()->count());
        $this->assertSame(39, Feature::query()->count());
        $this->assertSame(6, BarcodeCategory::query()->count());
        $this->assertSame(8, BarcodeType::query()->count());
        $this->assertSame(5, PaymentProvider::query()->count());
    }
}
