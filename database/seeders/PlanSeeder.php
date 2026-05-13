<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'For testing BarcodeOS with essential barcode generation basics.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency' => 'USD',
                'trial_days' => 0,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
                'metadata' => [
                    'headline' => 'Start with core barcode generation',
                    'cta_label' => 'Get Started',
                ],
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For small teams that need branded exports and longer history.',
                'monthly_price' => 19,
                'yearly_price' => 190,
                'currency' => 'USD',
                'trial_days' => 14,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 2,
                'metadata' => [
                    'headline' => 'Upgrade to clean exports',
                    'cta_label' => 'View Plan',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For professionals who need templates, PDF exports and bulk workflows.',
                'monthly_price' => 49,
                'yearly_price' => 490,
                'currency' => 'USD',
                'trial_days' => 14,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 3,
                'metadata' => [
                    'headline' => 'Best fit for daily operations',
                    'cta_label' => 'View Plan',
                    'recommended' => true,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For teams that need API access, higher limits and invoice support.',
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'currency' => 'USD',
                'trial_days' => 14,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 4,
                'metadata' => [
                    'headline' => 'Built for integrated barcode operations',
                    'cta_label' => 'Coming Soon',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For custom commercial usage, advanced access control and tailored limits.',
                'monthly_price' => null,
                'yearly_price' => null,
                'currency' => 'USD',
                'trial_days' => 30,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 5,
                'metadata' => [
                    'headline' => 'Custom rollout for enterprise teams',
                    'cta_label' => 'View Plan',
                    'custom_pricing' => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
