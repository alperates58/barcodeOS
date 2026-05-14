<?php

namespace Tests\Unit;

use App\Models\BarcodeCategory;
use App\Models\BarcodeParameter;
use App\Models\BarcodeType;
use App\Services\Barcode\ParameterSchemaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterSchemaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_schema_from_barcode_type_parameter_schema(): void
    {
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'label' => 'Width',
                    'type' => 'integer',
                    'required' => true,
                    'default' => 300,
                    'min' => 50,
                    'max' => 1200,
                    'options' => ['100', '200'],
                    'help_text' => 'Used by future forms.',
                    'available_features' => ['barcode.advanced_parameters'],
                    'sort_order' => 9,
                ],
            ],
        ]);

        $resolved = app(ParameterSchemaResolver::class)->resolveFor($barcodeType);

        $this->assertCount(1, $resolved);
        $this->assertSame([
            'key' => 'width',
            'label' => 'Width',
            'type' => 'integer',
            'required' => true,
            'default' => 300,
            'has_default' => true,
            'min' => 50,
            'max' => 1200,
            'options' => ['100', '200'],
            'help_text' => 'Used by future forms.',
            'available_features' => ['barcode.advanced_parameters'],
            'sort_order' => 9,
            'source' => 'schema.0',
        ], $resolved[0]);
    }

    public function test_it_includes_active_barcode_parameters_and_ignores_inactive_ones(): void
    {
        $barcodeType = $this->makeBarcodeType();

        BarcodeParameter::query()->create([
            'barcode_type_id' => $barcodeType->id,
            'label' => 'Foreground',
            'key' => 'foreground_color',
            'type' => 'color',
            'default_value' => '#000000',
            'help_text' => 'Primary ink color',
            'is_required' => false,
            'available_features' => [],
            'sort_order' => 2,
            'is_active' => true,
            'metadata' => [],
        ]);

        BarcodeParameter::query()->create([
            'barcode_type_id' => $barcodeType->id,
            'label' => 'Old field',
            'key' => 'old_field',
            'type' => 'text',
            'is_required' => false,
            'available_features' => [],
            'sort_order' => 3,
            'is_active' => false,
            'metadata' => [],
        ]);

        $resolved = app(ParameterSchemaResolver::class)->resolveFor($barcodeType->fresh());

        $this->assertCount(1, $resolved);
        $this->assertSame('foreground_color', $resolved[0]['key']);
        $this->assertSame('barcode_parameter', $resolved[0]['source']);
    }

    public function test_barcode_type_parameter_schema_wins_on_duplicate_key(): void
    {
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'label' => 'Schema Width',
                    'type' => 'integer',
                    'default' => 300,
                    'sort_order' => 1,
                ],
            ],
        ]);

        BarcodeParameter::query()->create([
            'barcode_type_id' => $barcodeType->id,
            'label' => 'Model Width',
            'key' => 'width',
            'type' => 'integer',
            'default_value' => '999',
            'is_required' => false,
            'available_features' => [],
            'sort_order' => 99,
            'is_active' => true,
            'metadata' => [],
        ]);

        $resolved = app(ParameterSchemaResolver::class)->resolveFor($barcodeType->fresh());

        $this->assertCount(1, $resolved);
        $this->assertSame('Schema Width', $resolved[0]['label']);
        $this->assertSame(300, $resolved[0]['default']);
        $this->assertSame('schema.0', $resolved[0]['source']);
    }

    public function test_it_handles_malformed_schema_safely(): void
    {
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                'bad-definition',
                [
                    'key' => 'valid_key',
                    'type' => 'text',
                ],
                [
                    'key' => 'bad_type',
                    'type' => 'json',
                ],
            ],
        ]);

        $resolver = app(ParameterSchemaResolver::class);

        $resolved = $resolver->resolveFor($barcodeType);
        $issues = $resolver->issuesFor($barcodeType);

        $this->assertCount(1, $resolved);
        $this->assertSame('valid_key', $resolved[0]['key']);
        $this->assertCount(2, $issues);
    }

    protected function makeBarcodeType(array $overrides = []): BarcodeType
    {
        $category = BarcodeCategory::query()->create([
            'name' => '2D',
            'slug' => '2d',
            'is_active' => true,
            'sort_order' => 1,
            'metadata' => [],
        ]);

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
