<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'free' => [
                'barcode.generate' => ['enabled' => true],
                'barcode.history' => ['enabled' => true],
                'export.png' => ['enabled' => true],
                'barcode.retail_types' => ['enabled' => true],
                'daily_generation_limit' => ['enabled' => true, 'limit_value' => 10],
                'monthly_generation_limit' => ['enabled' => true, 'limit_value' => 300],
                'history_retention_days' => ['enabled' => true, 'limit_value' => 7],
                'file_retention_days' => ['enabled' => true, 'limit_value' => 1],
                'team_member_limit' => ['enabled' => true, 'limit_value' => 1],
            ],
            'starter' => [
                'barcode.generate' => ['enabled' => true],
                'barcode.history' => ['enabled' => true],
                'export.png' => ['enabled' => true],
                'export.svg' => ['enabled' => true],
                'watermark.remove' => ['enabled' => true],
                'barcode.retail_types' => ['enabled' => true],
                'billing.invoices' => ['enabled' => true],
                'billing.tax_fields' => ['enabled' => true],
                'branding.custom_colors' => ['enabled' => true],
                'daily_generation_limit' => ['enabled' => true, 'limit_value' => 100],
                'monthly_generation_limit' => ['enabled' => true, 'limit_value' => 1000],
                'history_retention_days' => ['enabled' => true, 'limit_value' => 30],
                'file_retention_days' => ['enabled' => true, 'limit_value' => 30],
                'team_member_limit' => ['enabled' => true, 'limit_value' => 1],
            ],
            'pro' => [
                'barcode.generate' => ['enabled' => true],
                'barcode.history' => ['enabled' => true],
                'barcode.template.save' => ['enabled' => true],
                'barcode.advanced_parameters' => ['enabled' => true],
                'export.png' => ['enabled' => true],
                'export.svg' => ['enabled' => true],
                'export.pdf' => ['enabled' => true],
                'export.zip' => ['enabled' => true],
                'bulk.generate' => ['enabled' => true],
                'bulk.csv_upload' => ['enabled' => true],
                'bulk.excel_upload' => ['enabled' => true],
                'bulk.pdf_sheet_export' => ['enabled' => true],
                'bulk.error_report' => ['enabled' => true],
                'barcode.payment_qr' => ['enabled' => true],
                'barcode.retail_types' => ['enabled' => true],
                'watermark.remove' => ['enabled' => true],
                'billing.invoices' => ['enabled' => true],
                'billing.tax_fields' => ['enabled' => true],
                'branding.custom_logo' => ['enabled' => true],
                'branding.custom_colors' => ['enabled' => true],
                'daily_generation_limit' => ['enabled' => true, 'limit_value' => 1000],
                'monthly_generation_limit' => ['enabled' => true, 'limit_value' => 10000],
                'bulk_monthly_job_limit' => ['enabled' => true, 'limit_value' => 50],
                'bulk_max_rows_per_job' => ['enabled' => true, 'limit_value' => 1000],
                'history_retention_days' => ['enabled' => true, 'limit_value' => 180],
                'file_retention_days' => ['enabled' => true, 'limit_value' => 180],
                'team_member_limit' => ['enabled' => true, 'limit_value' => 3],
            ],
            'business' => [
                'barcode.generate' => ['enabled' => true],
                'barcode.history' => ['enabled' => true],
                'barcode.template.save' => ['enabled' => true],
                'barcode.advanced_parameters' => ['enabled' => true],
                'export.png' => ['enabled' => true],
                'export.svg' => ['enabled' => true],
                'export.pdf' => ['enabled' => true],
                'export.eps' => ['enabled' => true],
                'export.zip' => ['enabled' => true],
                'bulk.generate' => ['enabled' => true],
                'bulk.csv_upload' => ['enabled' => true],
                'bulk.excel_upload' => ['enabled' => true],
                'bulk.pdf_sheet_export' => ['enabled' => true],
                'bulk.error_report' => ['enabled' => true],
                'api.access' => ['enabled' => true],
                'api.keys.manage' => ['enabled' => true],
                'api.logs.view' => ['enabled' => true],
                'api.advanced_rate_limits' => ['enabled' => true],
                'team.members' => ['enabled' => true],
                'team.roles' => ['enabled' => true],
                'team.invites' => ['enabled' => true],
                'billing.invoices' => ['enabled' => true],
                'billing.tax_fields' => ['enabled' => true],
                'billing.manual_payment' => ['enabled' => true],
                'gs1.advanced' => ['enabled' => true],
                'barcode.payment_qr' => ['enabled' => true],
                'barcode.retail_types' => ['enabled' => true],
                'barcode.logistics_types' => ['enabled' => true],
                'watermark.remove' => ['enabled' => true],
                'branding.custom_logo' => ['enabled' => true],
                'branding.custom_colors' => ['enabled' => true],
                'daily_generation_limit' => ['enabled' => true, 'limit_value' => 5000],
                'monthly_generation_limit' => ['enabled' => true, 'limit_value' => 50000],
                'api_monthly_request_limit' => ['enabled' => true, 'limit_value' => 50000],
                'bulk_monthly_job_limit' => ['enabled' => true, 'limit_value' => 250],
                'bulk_max_rows_per_job' => ['enabled' => true, 'limit_value' => 10000],
                'history_retention_days' => ['enabled' => true, 'limit_value' => 365],
                'file_retention_days' => ['enabled' => true, 'limit_value' => 365],
                'team_member_limit' => ['enabled' => true, 'limit_value' => 10],
            ],
            'enterprise' => [
                'barcode.generate' => ['enabled' => true],
                'barcode.history' => ['enabled' => true],
                'barcode.template.save' => ['enabled' => true],
                'barcode.advanced_parameters' => ['enabled' => true],
                'export.png' => ['enabled' => true],
                'export.svg' => ['enabled' => true],
                'export.pdf' => ['enabled' => true],
                'export.eps' => ['enabled' => true],
                'export.zip' => ['enabled' => true],
                'bulk.generate' => ['enabled' => true],
                'bulk.csv_upload' => ['enabled' => true],
                'bulk.excel_upload' => ['enabled' => true],
                'bulk.pdf_sheet_export' => ['enabled' => true],
                'bulk.error_report' => ['enabled' => true],
                'api.access' => ['enabled' => true],
                'api.keys.manage' => ['enabled' => true],
                'api.logs.view' => ['enabled' => true],
                'api.advanced_rate_limits' => ['enabled' => true],
                'team.members' => ['enabled' => true],
                'team.roles' => ['enabled' => true],
                'team.invites' => ['enabled' => true],
                'billing.invoices' => ['enabled' => true],
                'billing.tax_fields' => ['enabled' => true],
                'billing.manual_payment' => ['enabled' => true],
                'gs1.advanced' => ['enabled' => true],
                'barcode.payment_qr' => ['enabled' => true],
                'barcode.retail_types' => ['enabled' => true],
                'barcode.logistics_types' => ['enabled' => true],
                'watermark.remove' => ['enabled' => true],
                'branding.custom_logo' => ['enabled' => true],
                'branding.custom_colors' => ['enabled' => true],
                'daily_generation_limit' => ['enabled' => true, 'limit_value' => 20000],
                'monthly_generation_limit' => ['enabled' => true, 'limit_value' => 250000],
                'api_monthly_request_limit' => ['enabled' => true, 'limit_value' => 150000],
                'bulk_monthly_job_limit' => ['enabled' => true, 'limit_value' => 1000],
                'bulk_max_rows_per_job' => ['enabled' => true, 'limit_value' => 50000],
                'history_retention_days' => ['enabled' => true, 'limit_value' => 365],
                'file_retention_days' => ['enabled' => true, 'limit_value' => 365],
                'team_member_limit' => ['enabled' => true, 'limit_value' => 50],
            ],
        ];

        $plans = Plan::query()->get()->keyBy('slug');
        $features = Feature::query()->get()->keyBy('key');

        foreach ($matrix as $planSlug => $featureMap) {
            $plan = $plans->get($planSlug);

            if (! $plan) {
                continue;
            }

            foreach ($featureMap as $featureKey => $settings) {
                $feature = $features->get($featureKey);

                if (! $feature) {
                    continue;
                }

                PlanFeature::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_id' => $feature->id,
                    ],
                    [
                        'enabled' => $settings['enabled'] ?? false,
                        'value' => isset($settings['limit_value']) ? (string) $settings['limit_value'] : ($settings['value'] ?? null),
                        'limit_value' => $settings['limit_value'] ?? null,
                        'metadata' => $settings['metadata'] ?? [],
                    ],
                );
            }
        }
    }
}
