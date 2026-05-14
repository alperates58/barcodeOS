<?php

namespace Database\Seeders;

use App\Models\BarcodeCategory;
use App\Models\BarcodeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BarcodeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $categories = BarcodeCategory::query()->get()->keyBy('slug');

        $types = [
            [
                'name' => 'QR Code',
                'slug' => 'qr-code',
                'category_slug' => '2d',
                'status' => 'active',
                'example_value' => 'https://barcodeos.com',
                'default_width' => 512,
                'default_height' => 512,
                'default_margin' => 16,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate'],
            ],
            [
                'name' => 'Code 128',
                'slug' => 'code-128',
                'category_slug' => 'linear-1d',
                'status' => 'active',
                'example_value' => 'BARCODE-128-001',
                'default_width' => 420,
                'default_height' => 140,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate'],
            ],
            [
                'name' => 'Code 39',
                'slug' => 'code-39',
                'category_slug' => 'linear-1d',
                'status' => 'active',
                'example_value' => 'CODE39',
                'default_width' => 420,
                'default_height' => 140,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate'],
            ],
            [
                'name' => 'EAN-13',
                'slug' => 'ean-13',
                'category_slug' => 'retail',
                'status' => 'active',
                'example_value' => '5901234123457',
                'default_width' => 380,
                'default_height' => 160,
                'default_margin' => 10,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate', 'barcode.retail_types'],
            ],
            [
                'name' => 'UPC-A',
                'slug' => 'upc-a',
                'category_slug' => 'retail',
                'status' => 'active',
                'example_value' => '036000291452',
                'default_width' => 380,
                'default_height' => 160,
                'default_margin' => 10,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate', 'barcode.retail_types'],
            ],
            [
                'name' => 'Data Matrix',
                'slug' => 'data-matrix',
                'category_slug' => '2d',
                'status' => 'active',
                'description' => 'Standard Data Matrix configuration. GS1 parsing remains disabled unless explicitly configured.',
                'example_value' => 'DMX-42-ALPHA',
                'validation_rules' => [
                    'required' => true,
                ],
                'default_width' => 420,
                'default_height' => 420,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate'],
                'documentation' => 'Normal Data Matrix remains separate from GS1 DataMatrix in this phase. Rendering is not implemented yet.',
                'seo_description' => 'Standard Data Matrix barcode type metadata for pre-render validation workflows.',
            ],
            [
                'name' => 'GS1 DataMatrix',
                'slug' => 'gs1-datamatrix',
                'category_slug' => 'gs1',
                'status' => 'active',
                'description' => 'GS1 DataMatrix parser/validation foundation. Rendering is not implemented yet.',
                'example_value' => '(01)12345678901234(21)ABC123(93)XYZ',
                'validation_rules' => [
                    'required' => true,
                    'gs1_datamatrix' => true,
                ],
                'default_width' => 420,
                'default_height' => 420,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate', 'gs1.advanced'],
                'parameter_schema' => [],
                'documentation' => 'GS1 DataMatrix parsing and validation are available in this phase. Rendering and export generation remain future work.',
                'seo_title' => 'GS1 DataMatrix Barcode',
                'seo_description' => 'GS1 DataMatrix barcode type metadata with parser and validation foundation, without rendering yet.',
            ],
            [
                'name' => 'PDF417',
                'slug' => 'pdf417',
                'category_slug' => 'postal-logistics',
                'status' => 'active',
                'example_value' => 'PDF417-CARRIER-001',
                'default_width' => 540,
                'default_height' => 220,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate', 'barcode.logistics_types'],
            ],
        ];

        foreach ($types as $index => $type) {
            $category = $categories->get($type['category_slug']);

            if (! $category) {
                continue;
            }

            BarcodeType::query()->updateOrCreate(
                ['slug' => $type['slug'] ?? Str::slug($type['name'])],
                [
                    'barcode_category_id' => $category->id,
                    'name' => $type['name'],
                    'description' => $type['description'] ?? null,
                    'status' => $type['status'],
                    'icon' => null,
                    'example_value' => $type['example_value'],
                    'validation_rules' => $type['validation_rules'] ?? [],
                    'default_width' => $type['default_width'],
                    'default_height' => $type['default_height'],
                    'default_margin' => $type['default_margin'],
                    'default_format' => $type['default_format'],
                    'supported_export_formats' => $type['supported_export_formats'],
                    'required_features' => $type['required_features'],
                    'parameter_schema' => $type['parameter_schema'] ?? [],
                    'documentation' => $type['documentation'] ?? null,
                    'seo_title' => $type['seo_title'] ?? $type['name'],
                    'seo_description' => $type['seo_description'] ?? null,
                    'sort_order' => $index + 1,
                    'metadata' => [],
                ],
            );
        }
    }
}
