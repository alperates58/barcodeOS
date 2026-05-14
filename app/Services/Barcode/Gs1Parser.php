<?php

namespace App\Services\Barcode;

class Gs1Parser
{
    public const GS = "\x1D";

    public function parse(?string $input): array
    {
        $rawInput = (string) ($input ?? '');
        $cleanedInput = $this->cleanRawInput($rawInput);

        if ($cleanedInput === '') {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $cleanedInput,
                'missing_gtin',
                'A GS1 GTIN value is required after AI 01.',
            );
        }

        $normalizedInput = $this->normalizeInput($cleanedInput);

        if ($this->containsParenthesizedAiSyntax($cleanedInput) && $normalizedInput === null) {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $cleanedInput,
                'invalid_parenthesized_ai_format',
                'The parenthesized GS1 AI format is invalid.',
            );
        }

        $normalizedInput ??= $cleanedInput;

        if (! str_starts_with($normalizedInput, '01')) {
            return $this->nonGs1Result($rawInput, $cleanedInput, $normalizedInput);
        }

        return $this->parseGs1Candidate($rawInput, $cleanedInput, $normalizedInput);
    }

    protected function parseGs1Candidate(string $rawInput, string $cleanedInput, string $normalizedInput): array
    {
        $valueAfterAi01 = substr($normalizedInput, 2);

        if ($valueAfterAi01 === '') {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'missing_gtin',
                'A GS1 GTIN value is required after AI 01.',
            );
        }

        if (strlen($valueAfterAi01) < 14) {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'invalid_gtin_length',
                'The GS1 GTIN value after AI 01 must be exactly 14 digits.',
                ['expected_length' => 14],
            );
        }

        $gtin = substr($normalizedInput, 2, 14);

        if (strlen($gtin) !== 14) {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'invalid_gtin_length',
                'The GS1 GTIN value after AI 01 must be exactly 14 digits.',
                ['expected_length' => 14],
            );
        }

        if (! ctype_digit($gtin)) {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'invalid_gtin_format',
                'The GS1 GTIN value after AI 01 must contain only digits.',
            );
        }

        $afterGtin = substr($normalizedInput, 16);

        if ($afterGtin === '' || ! str_starts_with($afterGtin, '21')) {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'missing_ai_21',
                'AI 21 is required after AI 01 and the 14-digit GTIN.',
            );
        }

        [$serial, $after21] = $this->extractVariableAiBody($afterGtin, '21', ['91', '92', '93']);

        if ($serial === '') {
            return $this->invalidGs1Result(
                $rawInput,
                $cleanedInput,
                $normalizedInput,
                'missing_serial',
                'AI 21 must include a serial value.',
            );
        }

        if (str_starts_with($after21, '93')) {
            [$value93, $after93] = $this->extractVariableAiBody($after21, '93', ['91', '92']);

            if ($value93 === '') {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'unsupported_gs1_structure',
                    'AI 93 must include a value for the supported short GS1 DataMatrix structure.',
                );
            }

            if ($after93 !== '') {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'unexpected_data_after_ai_93',
                    'Unexpected data was found after AI 93.',
                    ['remaining' => $after93],
                );
            }

            $encodeData = self::GS.'01'.$gtin.'21'.$serial.self::GS.'93'.$value93;

            return $this->validGs1Result(
                rawInput: $rawInput,
                cleanedInput: $cleanedInput,
                normalizedInput: $normalizedInput,
                codeType: 'gs1_datamatrix_short',
                encodeData: $encodeData,
                aiText: "(01){$gtin}(21){$serial}(93){$value93}",
                gtin: $gtin,
                serial: $serial,
                aiValues: [
                    '01' => $gtin,
                    '21' => $serial,
                    '93' => $value93,
                ],
            );
        }

        if (str_starts_with($after21, '91')) {
            [$value91, $after91] = $this->extractVariableAiBody($after21, '91', ['92', '93']);

            if ($value91 === '') {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'unsupported_gs1_structure',
                    'AI 91 must include a value for the supported long GS1 DataMatrix structure.',
                );
            }

            if (! str_starts_with($after91, '92')) {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'missing_ai_92',
                    'AI 92 is required after AI 91 for the supported long GS1 DataMatrix structure.',
                );
            }

            [$value92, $after92] = $this->extractVariableAiBody($after91, '92', []);

            if ($value92 === '') {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'missing_ai_92',
                    'AI 92 is required after AI 91 for the supported long GS1 DataMatrix structure.',
                );
            }

            if ($after92 !== '') {
                return $this->invalidGs1Result(
                    $rawInput,
                    $cleanedInput,
                    $normalizedInput,
                    'unsupported_gs1_structure',
                    'Only the supported GS1 DataMatrix long structure is allowed in this phase.',
                    ['remaining' => $after92],
                );
            }

            $encodeData = self::GS.'01'.$gtin.'21'.$serial.self::GS.'91'.$value91.self::GS.'92'.$value92;

            return $this->validGs1Result(
                rawInput: $rawInput,
                cleanedInput: $cleanedInput,
                normalizedInput: $normalizedInput,
                codeType: 'gs1_datamatrix_long',
                encodeData: $encodeData,
                aiText: "(01){$gtin}(21){$serial}(91){$value91}(92){$value92}",
                gtin: $gtin,
                serial: $serial,
                aiValues: [
                    '01' => $gtin,
                    '21' => $serial,
                    '91' => $value91,
                    '92' => $value92,
                ],
            );
        }

        return $this->invalidGs1Result(
            $rawInput,
            $cleanedInput,
            $normalizedInput,
            'unsupported_gs1_structure',
            'Only the supported GS1 DataMatrix short and long structures are allowed in this phase.',
        );
    }

    protected function nonGs1Result(string $rawInput, string $cleanedInput, string $normalizedInput): array
    {
        return [
            'is_gs1' => false,
            'valid' => true,
            'code_type' => 'datamatrix',
            'raw_input' => $rawInput,
            'cleaned_input' => $cleanedInput,
            'normalized_input' => $normalizedInput,
            'encode_data' => $cleanedInput,
            'ai_text' => null,
            'gtin' => null,
            'serial' => null,
            'ai_values' => [],
            'errors' => [],
        ];
    }

    protected function validGs1Result(
        string $rawInput,
        string $cleanedInput,
        string $normalizedInput,
        string $codeType,
        string $encodeData,
        string $aiText,
        string $gtin,
        string $serial,
        array $aiValues,
    ): array {
        return [
            'is_gs1' => true,
            'valid' => true,
            'code_type' => $codeType,
            'raw_input' => $rawInput,
            'cleaned_input' => $cleanedInput,
            'normalized_input' => $normalizedInput,
            'encode_data' => $encodeData,
            'ai_text' => $aiText,
            'gtin' => $gtin,
            'serial' => $serial,
            'ai_values' => $aiValues,
            'errors' => [],
        ];
    }

    protected function invalidGs1Result(
        string $rawInput,
        string $cleanedInput,
        string $normalizedInput,
        string $code,
        string $message,
        array $meta = [],
    ): array {
        return [
            'is_gs1' => true,
            'valid' => false,
            'code_type' => 'gs1_datamatrix_invalid',
            'raw_input' => $rawInput,
            'cleaned_input' => $cleanedInput,
            'normalized_input' => $normalizedInput,
            'encode_data' => null,
            'ai_text' => null,
            'gtin' => null,
            'serial' => null,
            'ai_values' => [],
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                    'field' => 'data',
                    'meta' => $meta,
                ],
            ],
        ];
    }

    protected function cleanRawInput(string $input): string
    {
        $cleaned = $input;
        $leadingTokens = [
            "\xEF\xBB\xBF",
            "\u{FEFF}",
            "\u{200B}",
            "\u{200C}",
            "\u{200D}",
            "\x00",
        ];

        while (true) {
            $updated = $cleaned;

            foreach ($leadingTokens as $token) {
                if (str_starts_with($updated, $token)) {
                    $updated = substr($updated, strlen($token));
                }
            }

            $updated = preg_replace('/^\s+/u', '', $updated ?? '') ?? $updated;

            if ($updated === $cleaned) {
                break;
            }

            $cleaned = $updated;
        }

        return rtrim($cleaned, "\r\n");
    }

    protected function normalizeInput(string $input): ?string
    {
        $normalized = $this->interpretEscapeSequences($input);

        if (str_starts_with($normalized, self::GS)) {
            $normalized = substr($normalized, 1);
        }

        if (! $this->containsParenthesizedAiSyntax($normalized)) {
            return $normalized;
        }

        return $this->convertParenthesizedAiToRaw($normalized);
    }

    protected function containsParenthesizedAiSyntax(string $input): bool
    {
        return str_contains($input, '(') || str_contains($input, ')');
    }

    protected function convertParenthesizedAiToRaw(string $input): ?string
    {
        preg_match_all('/\((\d{2})\)/', $input, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0] === []) {
            return null;
        }

        $supportedAis = ['01', '21', '91', '92', '93'];
        $result = '';
        $cursor = 0;

        foreach ($matches[0] as $index => [$fullMatch, $offset]) {
            if ($offset !== $cursor) {
                return null;
            }

            $ai = $matches[1][$index][0];

            if (! in_array($ai, $supportedAis, true)) {
                return null;
            }

            $valueStart = $offset + strlen($fullMatch);
            $nextOffset = $matches[0][$index + 1][1] ?? strlen($input);
            $value = substr($input, $valueStart, $nextOffset - $valueStart);

            if ($value === false) {
                return null;
            }

            if (str_contains($value, '(') || str_contains($value, ')')) {
                return null;
            }

            $result .= $ai.$value;
            $cursor = $nextOffset;
        }

        if ($cursor !== strlen($input)) {
            return null;
        }

        return trim($result);
    }

    protected function interpretEscapeSequences(string $input): string
    {
        $result = '';
        $length = strlen($input);

        for ($index = 0; $index < $length; $index++) {
            $character = $input[$index];

            if ($character === '\\' && $index + 1 < $length) {
                $next = $input[$index + 1];

                if ($next === 'F') {
                    $result .= self::GS;
                    $index++;

                    continue;
                }

                if ($next === 'n') {
                    $result .= "\n";
                    $index++;

                    continue;
                }

                if ($next === 't') {
                    $result .= "\t";
                    $index++;

                    continue;
                }

                if ($next === '\\') {
                    $result .= '\\';
                    $index++;

                    continue;
                }
            }

            $result .= $character;
        }

        return $result;
    }

    protected function extractVariableAiBody(string $source, string $ai, array $nextPossibleAis): array
    {
        if (! str_starts_with($source, $ai)) {
            return ['', ''];
        }

        $remainder = substr($source, strlen($ai));
        $gsPosition = strpos($remainder, self::GS);

        if ($gsPosition !== false) {
            return [substr($remainder, 0, $gsPosition), substr($remainder, $gsPosition + 1)];
        }

        $nextAiPosition = null;

        foreach ($nextPossibleAis as $nextAi) {
            $position = strpos($remainder, $nextAi);

            if ($position !== false && ($nextAiPosition === null || $position < $nextAiPosition)) {
                $nextAiPosition = $position;
            }
        }

        if ($nextAiPosition !== null) {
            return [substr($remainder, 0, $nextAiPosition), substr($remainder, $nextAiPosition)];
        }

        return [$remainder, ''];
    }
}
