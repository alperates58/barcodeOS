<?php

namespace Tests\Unit;

use App\Models\BarcodeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gs1_datamatrix_is_seeded_with_expected_configuration(): void
    {
        $this->seed();

        $barcodeType = BarcodeType::query()->where('slug', 'gs1-datamatrix')->firstOrFail();

        $this->assertSame('GS1 DataMatrix', $barcodeType->name);
        $this->assertSame('gs1', $barcodeType->category?->slug);
        $this->assertSame('active', $barcodeType->status);
        $this->assertSame('(01)12345678901234(21)ABC123(93)XYZ', $barcodeType->example_value);
        $this->assertTrue((bool) ($barcodeType->validation_rules['required'] ?? false));
        $this->assertTrue((bool) ($barcodeType->validation_rules['gs1_datamatrix'] ?? false));
        $this->assertContains('barcode.generate', $barcodeType->required_features ?? []);
        $this->assertContains('gs1.advanced', $barcodeType->required_features ?? []);
        $this->assertSame(['png', 'svg'], $barcodeType->supported_export_formats);
        $this->assertSame('png', $barcodeType->default_format);
    }

    public function test_normal_data_matrix_seed_remains_non_gs1(): void
    {
        $this->seed();

        $barcodeType = BarcodeType::query()->where('slug', 'data-matrix')->firstOrFail();

        $this->assertSame('Data Matrix', $barcodeType->name);
        $this->assertSame('2d', $barcodeType->category?->slug);
        $this->assertFalse((bool) ($barcodeType->validation_rules['gs1_datamatrix'] ?? false));
        $this->assertNotContains('gs1.advanced', $barcodeType->required_features ?? []);
        $this->assertContains('barcode.generate', $barcodeType->required_features ?? []);
    }
}
