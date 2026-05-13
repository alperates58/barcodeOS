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
                'category_slug' => '2d',
                'status' => 'active',
                'example_value' => 'DMX-42-ALPHA',
                'default_width' => 420,
                'default_height' => 420,
                'default_margin' => 12,
                'default_format' => 'png',
                'supported_export_formats' => ['png', 'svg'],
                'required_features' => ['barcode.generate'],
            ],
            [
                'name' => 'PDF417',
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
                ['slug' => Str::slug($type['name'])],
                [
                    'barcode_category_id' => $category->id,
                    'name' => $type['name'],
                    'description' => null,
                    'status' => $type['status'],
                    'icon' => null,
                    'example_value' => $type['example_value'],
                    'validation_rules' => [],
                    'default_width' => $type['default_width'],
                    'default_height' => $type['default_height'],
                    'default_margin' => $type['default_margin'],
                    'default_format' => $type['default_format'],
                    'supported_export_formats' => $type['supported_export_formats'],
                    'required_features' => $type['required_features'],
                    'parameter_schema' => [],
                    'documentation' => null,
                    'seo_title' => $type['name'],
                    'seo_description' => null,
                    'sort_order' => $index + 1,
                    'metadata' => [],
                ],
            );
        }
    }
}
