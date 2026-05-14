<?php

namespace Tests\Unit;

use App\Models\BarcodeCategory;
use App\Models\BarcodeExport;
use App\Models\BarcodeParameter;
use App\Models\BarcodeType;
use App\Models\GeneratedBarcode;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Models\User;
use App\Services\Barcode\Gs1Parser;
use App\Services\Barcode\BarcodeValidationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_basic_request_passes_for_free_user_with_barcode_generate_and_export_png(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $barcodeType->update([
            'validation_rules' => ['required' => true, 'min_length' => 3],
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'default' => 300,
                    'min' => 50,
                    'max' => 2000,
                ],
            ],
        ]);

        $result = $service->validateGenerationRequest(
            $user,
            $barcodeType->fresh(),
            ' HELLO-123 ',
            ' PNG ',
            ['width' => '350', 'unknown' => 'ignored'],
        );

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('HELLO-123', $result['normalized']['data']);
        $this->assertSame('png', $result['normalized']['format']);
        $this->assertSame(['width' => 350], $result['normalized']['parameters']);
        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_missing_data_fails(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $result = $service->validateGenerationRequest($user, $barcodeType, '   ', 'png');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_required', $result['errors'][0]['code']);
        $this->assertSame('data', $result['errors'][0]['field']);
    }

    public function test_max_length_fails_when_exceeded(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['max_length' => 3],
        ]);

        $result = $service->validateData($barcodeType, 'ABCDE');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_too_long', $result['errors'][0]['code']);
    }

    public function test_min_length_fails_when_too_short(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['min_length' => 5],
        ]);

        $result = $service->validateData($barcodeType, 'AB');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_too_short', $result['errors'][0]['code']);
    }

    public function test_exact_length_fails_when_wrong(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['exact_length' => 13],
        ]);

        $result = $service->validateData($barcodeType, '123456789012');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_wrong_length', $result['errors'][0]['code']);
    }

    public function test_numeric_only_fails_for_non_numeric_data(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['numeric_only' => true],
        ]);

        $result = $service->validateData($barcodeType, 'ABC123');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_must_be_numeric', $result['errors'][0]['code']);
    }

    public function test_allowed_regex_passes_with_valid_data(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['allowed_regex' => '/^[A-Z0-9\\-]+$/'],
        ]);

        $result = $service->validateData($barcodeType, 'ABC-123');

        $this->assertTrue($result['valid']);
        $this->assertSame('ABC-123', $result['normalized']['data']);
    }

    public function test_allowed_regex_fails_invalid_data(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['allowed_regex' => '/^[A-Z0-9\\-]+$/'],
        ]);

        $result = $service->validateData($barcodeType, 'abc-123');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_invalid_format', $result['errors'][0]['code']);
    }

    public function test_invalid_allowed_regex_does_not_crash_and_returns_safe_error(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['allowed_regex' => '/(/'],
        ]);

        $result = $service->validateData($barcodeType, 'ABC123');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_validation_rule_invalid', $result['errors'][0]['code']);
    }

    public function test_allowed_characters_rejects_invalid_characters(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'validation_rules' => ['allowed_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-'],
        ]);

        $result = $service->validateData($barcodeType, 'ABC_123');

        $this->assertFalse($result['valid']);
        $this->assertSame('data_contains_invalid_characters', $result['errors'][0]['code']);
    }

    public function test_unsupported_export_format_fails(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $result = $service->validateExportFormat($user, $barcodeType, 'pdf');

        $this->assertFalse($result['valid']);
        $this->assertSame('export_format_not_supported', $result['errors'][0]['code']);
    }

    public function test_export_format_not_entitled_fails(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $barcodeType->update([
            'supported_export_formats' => ['png', 'pdf'],
            'default_format' => 'pdf',
        ]);

        $result = $service->validateExportFormat($user, $barcodeType->fresh(), 'pdf');

        $this->assertFalse($result['valid']);
        $this->assertSame('export_format_not_allowed', $result['errors'][0]['code']);
    }

    public function test_restricted_barcode_type_fails_when_user_lacks_required_feature(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'pdf417')->firstOrFail();

        $result = $service->validateBarcodeType($user, $barcodeType);

        $this->assertFalse($result['valid']);
        $this->assertSame('barcode_type_not_allowed', $result['errors'][0]['code']);
    }

    public function test_restricted_barcode_type_passes_when_user_has_all_required_features(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = $this->makeSubscribedUser('business');
        $barcodeType = BarcodeType::query()->where('slug', 'pdf417')->firstOrFail();

        $result = $service->validateBarcodeType($user, $barcodeType);

        $this->assertTrue($result['valid']);
    }

    public function test_daily_limit_reached_fails(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-14 10:00:00'));

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        UsageCounter::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'feature_key' => 'daily_generation_limit',
            'period_type' => 'daily',
            'period_start' => CarbonImmutable::parse('2026-05-14 00:00:00'),
            'period_end' => CarbonImmutable::parse('2026-05-14 23:59:59'),
            'used' => 10,
            'limit' => 10,
            'source' => 'web',
            'metadata' => [],
        ]);

        $result = $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $this->assertFalse($result['valid']);
        $this->assertTrue($this->hasErrorCode($result['errors'], 'daily_limit_reached'));
    }

    public function test_monthly_limit_reached_fails(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-14 10:00:00'));

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        UsageCounter::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'feature_key' => 'monthly_generation_limit',
            'period_type' => 'monthly',
            'period_start' => CarbonImmutable::parse('2026-05-01 00:00:00'),
            'period_end' => CarbonImmutable::parse('2026-05-31 23:59:59'),
            'used' => 300,
            'limit' => 300,
            'source' => 'web',
            'metadata' => [],
        ]);

        $result = $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $this->assertFalse($result['valid']);
        $this->assertTrue($this->hasErrorCode($result['errors'], 'monthly_limit_reached'));
    }

    public function test_validation_does_not_create_usage_counters(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $this->assertSame(0, UsageCounter::query()->count());
    }

    public function test_validation_does_not_increment_usage(): void
    {
        $this->seed();
        $this->travelTo(CarbonImmutable::parse('2026-05-14 10:00:00'));

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        UsageCounter::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'feature_key' => 'daily_generation_limit',
            'period_type' => 'daily',
            'period_start' => CarbonImmutable::parse('2026-05-14 00:00:00'),
            'period_end' => CarbonImmutable::parse('2026-05-14 23:59:59'),
            'used' => 2,
            'limit' => 10,
            'source' => 'web',
            'metadata' => [],
        ]);

        $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $counter = UsageCounter::query()->where('feature_key', 'daily_generation_limit')->firstOrFail();

        $this->assertSame(2, $counter->used);
    }

    public function test_validation_does_not_create_generated_barcodes(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $this->assertSame(0, GeneratedBarcode::query()->count());
    }

    public function test_validation_does_not_create_barcode_exports(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();

        $service->validateGenerationRequest($user, $barcodeType, 'ABC123', 'png');

        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_required_parameter_missing_fails(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
        ]);

        $result = $service->validateParameters($barcodeType, []);

        $this->assertFalse($result['valid']);
        $this->assertSame('parameter_required', $result['errors'][0]['code']);
    }

    public function test_missing_parameter_with_default_uses_default(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'default' => 300,
                ],
            ],
        ]);

        $result = $service->validateParameters($barcodeType, []);

        $this->assertTrue($result['valid']);
        $this->assertSame(['width' => 300], $result['normalized']['parameters']);
    }

    public function test_integer_parameter_min_max_works(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'min' => 50,
                    'max' => 200,
                ],
            ],
        ]);

        $tooLow = $service->validateParameters($barcodeType, ['width' => 20]);
        $tooHigh = $service->validateParameters($barcodeType, ['width' => 250]);
        $valid = $service->validateParameters($barcodeType, ['width' => 120]);

        $this->assertSame('parameter_too_low', $tooLow['errors'][0]['code']);
        $this->assertSame('parameter_too_high', $tooHigh['errors'][0]['code']);
        $this->assertTrue($valid['valid']);
        $this->assertSame(120, $valid['normalized']['parameters']['width']);
    }

    public function test_number_parameter_min_max_works(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'scale',
                    'type' => 'number',
                    'min' => 0.5,
                    'max' => 2.5,
                ],
            ],
        ]);

        $valid = $service->validateParameters($barcodeType, ['scale' => '1.5']);
        $invalid = $service->validateParameters($barcodeType, ['scale' => '3.0']);

        $this->assertTrue($valid['valid']);
        $this->assertSame(1.5, $valid['normalized']['parameters']['scale']);
        $this->assertSame('parameter_too_high', $invalid['errors'][0]['code']);
    }

    public function test_select_parameter_validates_options(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'error_correction',
                    'type' => 'select',
                    'options' => ['L', 'M', 'Q', 'H'],
                ],
            ],
        ]);

        $valid = $service->validateParameters($barcodeType, ['error_correction' => 'M']);
        $invalid = $service->validateParameters($barcodeType, ['error_correction' => 'X']);

        $this->assertTrue($valid['valid']);
        $this->assertSame('M', $valid['normalized']['parameters']['error_correction']);
        $this->assertSame('parameter_invalid_option', $invalid['errors'][0]['code']);
    }

    public function test_multi_select_parameter_validates_options(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'layers',
                    'type' => 'multi_select',
                    'options' => ['A', 'B', 'C'],
                ],
            ],
        ]);

        $valid = $service->validateParameters($barcodeType, ['layers' => ['A', 'C']]);
        $invalid = $service->validateParameters($barcodeType, ['layers' => ['A', 'X']]);

        $this->assertTrue($valid['valid']);
        $this->assertSame(['A', 'C'], $valid['normalized']['parameters']['layers']);
        $this->assertSame('parameter_invalid_option', $invalid['errors'][0]['code']);
    }

    public function test_boolean_parameter_normalizes_correctly(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'show_text',
                    'type' => 'boolean',
                ],
            ],
        ]);

        $result = $service->validateParameters($barcodeType, ['show_text' => 'true']);

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['normalized']['parameters']['show_text']);
    }

    public function test_color_parameter_validates_hex_values(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'foreground_color',
                    'type' => 'color',
                ],
            ],
        ]);

        $valid = $service->validateParameters($barcodeType, ['foreground_color' => '#fff']);
        $invalid = $service->validateParameters($barcodeType, ['foreground_color' => 'red']);

        $this->assertTrue($valid['valid']);
        $this->assertSame('#fff', $valid['normalized']['parameters']['foreground_color']);
        $this->assertSame('parameter_invalid_color', $invalid['errors'][0]['code']);
    }

    public function test_unknown_parameters_are_ignored_and_not_included_in_normalized_parameters(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'default' => 300,
                ],
            ],
        ]);

        $result = $service->validateParameters($barcodeType, [
            'width' => 400,
            'unknown_parameter' => 'ignored',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertSame(['width' => 400], $result['normalized']['parameters']);
        $this->assertArrayNotHasKey('unknown_parameter', $result['normalized']['parameters']);
    }

    public function test_parameter_schema_is_primary_and_active_barcode_parameters_are_complementary(): void
    {
        $this->seed();

        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                [
                    'key' => 'width',
                    'type' => 'integer',
                    'default' => 300,
                ],
            ],
        ]);

        BarcodeParameter::query()->create([
            'barcode_type_id' => $barcodeType->id,
            'label' => 'Foreground',
            'key' => 'foreground_color',
            'type' => 'color',
            'default_value' => '#000000',
            'is_required' => false,
            'is_active' => true,
            'options' => [],
            'available_features' => [],
            'metadata' => [],
        ]);

        $service = app(BarcodeValidationService::class);
        $result = $service->validateParameters($barcodeType->fresh(), []);

        $this->assertTrue($result['valid']);
        $this->assertSame([
            'width' => 300,
            'foreground_color' => '#000000',
        ], $result['normalized']['parameters']);
    }

    public function test_malformed_parameter_schema_returns_safe_error_without_crashing(): void
    {
        $this->seed();

        $barcodeType = $this->makeBarcodeType([
            'parameter_schema' => [
                'invalid-definition',
            ],
        ]);

        $service = app(BarcodeValidationService::class);
        $result = $service->validateParameters($barcodeType, []);

        $this->assertFalse($result['valid']);
        $this->assertSame('parameter_schema_invalid', $result['errors'][0]['code']);
    }

    public function test_inactive_barcode_type_fails_validation(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $user = User::factory()->create();
        $barcodeType = BarcodeType::query()->where('slug', 'qr-code')->firstOrFail();
        $barcodeType->update(['status' => 'archived']);

        $result = $service->validateBarcodeType($user, $barcodeType->fresh());

        $this->assertFalse($result['valid']);
        $this->assertSame('barcode_type_inactive', $result['errors'][0]['code']);
    }

    public function test_normal_data_matrix_remains_valid_and_is_not_forced_into_gs1_validation(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = BarcodeType::query()->where('slug', 'data-matrix')->firstOrFail();
        $barcodeType->update([
            'validation_rules' => ['gs1_datamatrix' => true],
        ]);

        $result = $service->validateData($barcodeType->fresh(), 'DMX-42-ALPHA');

        $this->assertTrue($result['valid']);
        $this->assertSame('DMX-42-ALPHA', $result['normalized']['data']);
    }

    public function test_gs1_parser_errors_are_mapped_into_common_validation_result_structure(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'slug' => 'gs1-datamatrix',
            'validation_rules' => ['gs1_datamatrix' => true],
        ]);

        $result = $service->validateData($barcodeType, '011234567890123421ABC12391VAL1');

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_ai_92', $result['errors'][0]['code']);
        $this->assertSame('data', $result['errors'][0]['field']);
    }

    public function test_valid_gs1_datamatrix_passes_validation_without_rendering(): void
    {
        $this->seed();

        $service = app(BarcodeValidationService::class);
        $barcodeType = $this->makeBarcodeType([
            'slug' => 'gs1-data-matrix',
            'validation_rules' => ['gs1_datamatrix' => true],
        ]);

        $result = $service->validateData($barcodeType, '011234567890123421ABC12393XYZ');

        $this->assertTrue($result['valid']);
        $this->assertSame(
            Gs1Parser::GS.'011234567890123421ABC123'.Gs1Parser::GS.'93XYZ',
            $result['normalized']['data']
        );
        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    protected function makeSubscribedUser(string $planSlug): User
    {
        $user = User::factory()->create();
        $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        return $user;
    }

    protected function makeBarcodeType(array $overrides = []): BarcodeType
    {
        $category = BarcodeCategory::query()->firstOrFail();

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

    protected function hasErrorCode(array $errors, string $code): bool
    {
        foreach ($errors as $error) {
            if (($error['code'] ?? null) === $code) {
                return true;
            }
        }

        return false;
    }
}
