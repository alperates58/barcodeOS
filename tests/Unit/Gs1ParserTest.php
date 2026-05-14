<?php

namespace Tests\Unit;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Services\Barcode\Gs1Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class Gs1ParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_datamatrix_input_is_treated_as_non_gs1_datamatrix(): void
    {
        $result = app(Gs1Parser::class)->parse('DMX-42-ALPHA');

        $this->assertFalse($result['is_gs1']);
        $this->assertTrue($result['valid']);
        $this->assertSame('datamatrix', $result['code_type']);
        $this->assertSame('DMX-42-ALPHA', $result['encode_data']);
        $this->assertNull($result['ai_text']);
        $this->assertSame([], $result['errors']);
    }

    public function test_valid_gs1_short_structure_parses_successfully(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123421ABC12393XYZ');

        $this->assertTrue($result['is_gs1']);
        $this->assertTrue($result['valid']);
        $this->assertSame('gs1_datamatrix_short', $result['code_type']);
        $this->assertSame(Gs1Parser::GS.'011234567890123421ABC123'.Gs1Parser::GS.'93XYZ', $result['encode_data']);
        $this->assertSame('(01)12345678901234(21)ABC123(93)XYZ', $result['ai_text']);
        $this->assertSame('12345678901234', $result['gtin']);
        $this->assertSame('ABC123', $result['serial']);
        $this->assertSame([
            '01' => '12345678901234',
            '21' => 'ABC123',
            '93' => 'XYZ',
        ], $result['ai_values']);
    }

    public function test_valid_gs1_long_structure_parses_successfully(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123421ABC12391VAL192VAL2');

        $this->assertTrue($result['is_gs1']);
        $this->assertTrue($result['valid']);
        $this->assertSame('gs1_datamatrix_long', $result['code_type']);
        $this->assertSame(Gs1Parser::GS.'011234567890123421ABC123'.Gs1Parser::GS.'91VAL1'.Gs1Parser::GS.'92VAL2', $result['encode_data']);
        $this->assertSame('(01)12345678901234(21)ABC123(91)VAL1(92)VAL2', $result['ai_text']);
        $this->assertSame([
            '01' => '12345678901234',
            '21' => 'ABC123',
            '91' => 'VAL1',
            '92' => 'VAL2',
        ], $result['ai_values']);
    }

    public function test_missing_gtin_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('01');

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_gtin', $result['errors'][0]['code']);
    }

    public function test_invalid_gtin_length_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('01123452193XYZ');

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid_gtin_length', $result['errors'][0]['code']);
    }

    public function test_invalid_non_numeric_gtin_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('01123456789012AB21ABC12393XYZ');

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid_gtin_format', $result['errors'][0]['code']);
    }

    public function test_missing_ai_21_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123493XYZ');

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_ai_21', $result['errors'][0]['code']);
    }

    public function test_missing_serial_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('01123456789012342193XYZ');

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_serial', $result['errors'][0]['code']);
    }

    public function test_ai_91_exists_but_ai_92_missing_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123421ABC12391VAL1');

        $this->assertFalse($result['valid']);
        $this->assertSame('missing_ai_92', $result['errors'][0]['code']);
    }

    public function test_ai_93_followed_by_unexpected_data_fails(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123421ABC12393XYZ91TAIL');

        $this->assertFalse($result['valid']);
        $this->assertSame('unexpected_data_after_ai_93', $result['errors'][0]['code']);
    }

    public function test_parenthesized_ai_short_input_parses_successfully(): void
    {
        $result = app(Gs1Parser::class)->parse('(01)12345678901234(21)ABC123(93)XYZ');

        $this->assertTrue($result['valid']);
        $this->assertSame('gs1_datamatrix_short', $result['code_type']);
        $this->assertSame('011234567890123421ABC12393XYZ', $result['normalized_input']);
    }

    public function test_parenthesized_ai_long_input_parses_successfully(): void
    {
        $result = app(Gs1Parser::class)->parse('(01)12345678901234(21)ABC123(91)VAL1(92)VAL2');

        $this->assertTrue($result['valid']);
        $this->assertSame('gs1_datamatrix_long', $result['code_type']);
        $this->assertSame('011234567890123421ABC12391VAL192VAL2', $result['normalized_input']);
    }

    public function test_literal_f_separator_input_parses_and_normalizes_correctly(): void
    {
        $result = app(Gs1Parser::class)->parse('011234567890123421ABC123\F93XYZ');

        $this->assertTrue($result['valid']);
        $this->assertSame('011234567890123421ABC123'.Gs1Parser::GS.'93XYZ', $result['normalized_input']);
        $this->assertSame(Gs1Parser::GS.'011234567890123421ABC123'.Gs1Parser::GS.'93XYZ', $result['encode_data']);
    }

    public function test_bom_and_invisible_leading_characters_are_cleaned(): void
    {
        $result = app(Gs1Parser::class)->parse("\xEF\xBB\xBF\xE2\x80\x8B011234567890123421ABC12393XYZ");

        $this->assertTrue($result['valid']);
        $this->assertSame('011234567890123421ABC12393XYZ', $result['cleaned_input']);
    }

    public function test_invalid_parenthesized_ai_format_fails_safely(): void
    {
        $result = app(Gs1Parser::class)->parse('(01)12345678901234(21ABC123(93)XYZ');

        $this->assertFalse($result['valid']);
        $this->assertSame('invalid_parenthesized_ai_format', $result['errors'][0]['code']);
    }

    public function test_parser_does_not_create_usage_counters_generated_barcodes_or_barcode_exports(): void
    {
        $this->seed();

        app(Gs1Parser::class)->parse('011234567890123421ABC12393XYZ');

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }

    public function test_parser_has_no_entitlement_or_usage_service_dependencies(): void
    {
        $constructor = (new ReflectionClass(Gs1Parser::class))->getConstructor();

        $this->assertTrue($constructor === null || $constructor->getNumberOfParameters() === 0);
    }
}
