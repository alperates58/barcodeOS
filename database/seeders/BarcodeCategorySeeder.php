<?php

namespace Database\Seeders;

use App\Models\BarcodeCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BarcodeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Linear / 1D',
            'Retail',
            '2D',
            'GS1',
            'Postal & Logistics',
            'Payment & Banking',
        ];

        foreach ($categories as $index => $name) {
            BarcodeCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => null,
                    'icon' => null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'metadata' => [],
                    'seo_title' => $name,
                    'seo_description' => null,
                ],
            );
        }
    }
}
