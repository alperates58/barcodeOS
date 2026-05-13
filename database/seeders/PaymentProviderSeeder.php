<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'Stripe', 'slug' => 'stripe'],
            ['name' => 'Paddle', 'slug' => 'paddle'],
            ['name' => 'PayPal', 'slug' => 'paypal'],
            ['name' => 'iyzico', 'slug' => 'iyzico'],
            ['name' => 'Manual bank transfer', 'slug' => 'manual-bank-transfer'],
        ];

        foreach ($providers as $provider) {
            PaymentProvider::query()->updateOrCreate(
                ['slug' => $provider['slug']],
                [
                    'name' => $provider['name'],
                    'is_enabled' => false,
                    'mode' => 'sandbox',
                    'public_config' => [],
                    'secret_config' => null,
                    'metadata' => [
                        'managed_via_env' => true,
                    ],
                ],
            );
        }
    }
}
