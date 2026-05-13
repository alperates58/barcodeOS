<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LanguageSeeder::class,
            PlanSeeder::class,
            FeatureSeeder::class,
            PlanFeatureSeeder::class,
            BarcodeCategorySeeder::class,
            BarcodeTypeSeeder::class,
            PaymentProviderSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
