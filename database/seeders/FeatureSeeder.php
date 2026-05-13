<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['key' => 'barcode.generate', 'name' => 'Barcode Generation', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.history', 'name' => 'Barcode History', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.template.save', 'name' => 'Saved Templates', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.advanced_parameters', 'name' => 'Advanced Parameters', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'export.png', 'name' => 'PNG Export', 'category' => 'export', 'value_type' => 'boolean'],
            ['key' => 'export.svg', 'name' => 'SVG Export', 'category' => 'export', 'value_type' => 'boolean'],
            ['key' => 'export.pdf', 'name' => 'PDF Export', 'category' => 'export', 'value_type' => 'boolean'],
            ['key' => 'export.eps', 'name' => 'EPS Export', 'category' => 'export', 'value_type' => 'boolean'],
            ['key' => 'export.zip', 'name' => 'ZIP Export', 'category' => 'export', 'value_type' => 'boolean'],
            ['key' => 'bulk.generate', 'name' => 'Bulk Generation', 'category' => 'bulk', 'value_type' => 'boolean'],
            ['key' => 'bulk.csv_upload', 'name' => 'Bulk CSV Upload', 'category' => 'bulk', 'value_type' => 'boolean'],
            ['key' => 'bulk.excel_upload', 'name' => 'Bulk Excel Upload', 'category' => 'bulk', 'value_type' => 'boolean'],
            ['key' => 'bulk.pdf_sheet_export', 'name' => 'Bulk PDF Sheet Export', 'category' => 'bulk', 'value_type' => 'boolean'],
            ['key' => 'bulk.error_report', 'name' => 'Bulk Error Reports', 'category' => 'bulk', 'value_type' => 'boolean'],
            ['key' => 'api.access', 'name' => 'API Access', 'category' => 'api', 'value_type' => 'boolean'],
            ['key' => 'api.keys.manage', 'name' => 'API Key Management', 'category' => 'api', 'value_type' => 'boolean'],
            ['key' => 'api.logs.view', 'name' => 'API Logs', 'category' => 'api', 'value_type' => 'boolean'],
            ['key' => 'api.advanced_rate_limits', 'name' => 'Advanced API Rate Limits', 'category' => 'api', 'value_type' => 'boolean'],
            ['key' => 'team.members', 'name' => 'Team Members', 'category' => 'team', 'value_type' => 'boolean'],
            ['key' => 'team.roles', 'name' => 'Team Roles', 'category' => 'team', 'value_type' => 'boolean'],
            ['key' => 'team.invites', 'name' => 'Team Invites', 'category' => 'team', 'value_type' => 'boolean'],
            ['key' => 'billing.invoices', 'name' => 'Invoices', 'category' => 'billing', 'value_type' => 'boolean'],
            ['key' => 'billing.tax_fields', 'name' => 'Tax Fields', 'category' => 'billing', 'value_type' => 'boolean'],
            ['key' => 'billing.manual_payment', 'name' => 'Manual Payment', 'category' => 'billing', 'value_type' => 'boolean'],
            ['key' => 'gs1.advanced', 'name' => 'Advanced GS1', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.payment_qr', 'name' => 'Payment QR Types', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.retail_types', 'name' => 'Retail Barcode Types', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'barcode.logistics_types', 'name' => 'Logistics Barcode Types', 'category' => 'barcode', 'value_type' => 'boolean'],
            ['key' => 'watermark.remove', 'name' => 'Remove Watermark', 'category' => 'branding', 'value_type' => 'boolean'],
            ['key' => 'branding.custom_logo', 'name' => 'Custom Logo', 'category' => 'branding', 'value_type' => 'boolean'],
            ['key' => 'branding.custom_colors', 'name' => 'Custom Colors', 'category' => 'branding', 'value_type' => 'boolean'],
            ['key' => 'daily_generation_limit', 'name' => 'Daily Generation Limit', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'monthly_generation_limit', 'name' => 'Monthly Generation Limit', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'api_monthly_request_limit', 'name' => 'Monthly API Limit', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'bulk_monthly_job_limit', 'name' => 'Monthly Bulk Job Limit', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'bulk_max_rows_per_job', 'name' => 'Bulk Rows Per Job', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'history_retention_days', 'name' => 'History Retention Days', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'file_retention_days', 'name' => 'File Retention Days', 'category' => 'limits', 'value_type' => 'integer'],
            ['key' => 'team_member_limit', 'name' => 'Team Member Limit', 'category' => 'limits', 'value_type' => 'integer'],
        ];

        foreach ($features as $index => $feature) {
            Feature::query()->updateOrCreate(
                ['key' => $feature['key']],
                $feature + [
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'metadata' => [],
                ],
            );
        }
    }
}
