<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'app', 'key' => 'default_locale', 'value' => 'en', 'type' => 'string', 'is_public' => true],
            ['group' => 'app', 'key' => 'fallback_locale', 'value' => 'en', 'type' => 'string', 'is_public' => false],
            ['group' => 'billing', 'key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'is_public' => true],
            ['group' => 'barcode', 'key' => 'default_export_format', 'value' => 'png', 'type' => 'string', 'is_public' => true],
            ['group' => 'storage', 'key' => 'development_disk', 'value' => 'local', 'type' => 'string', 'is_public' => false],
            ['group' => 'storage', 'key' => 'production_disk', 'value' => 's3', 'type' => 'string', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                [
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                ],
                $setting + ['metadata' => []],
            );
        }
    }
}
