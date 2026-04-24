<?php

declare(strict_types=1);

namespace Dmc\Services;

final class Gs1Parser
{
    public const GS = "\x1D";

    public function normalize(string $rawLine): array
    {
        $original = $rawLine;
        $s = $this->removeLeadingBomAndInvisible($rawLine);
        $s = rtrim($s, "\r\n");

        if (trim($s) === '') {
            return $this->fail('Satır boş.');
        }

        $s = $this->interpretEscapeSequences($s);

        if (str_starts_with($s, self::GS)) {
            $s = substr($s, 1);
        }

        if ($this->looksLikeParenthesizedAi($s)) {
            $s = $this->convertParenthesizedAiToRaw($s);
        }

        if (!str_starts_with($s, '01')) {
            return [
                'success' => true,
                'is_gs1' => false,
                'code_type' => 'normal_datamatrix',
                'original' => $original,
                'normalized' => $s,
                'ai_text' => $s,
                'error' => null,
            ];
        }

        if (strlen($s) < 16) {
            return $this->fail('GS1 gibi görünüyor ama 01 alanı tamamlanmamış.');
        }

        $gtin = substr($s, 2, 14);
        if (!ctype_digit($gtin) || strlen($gtin) !== 14) {
            return $this->fail('01 alanından sonra 14 haneli GTIN gelmeli.');
        }

        $rest = substr($s, 16);
        if ($rest === '') {
            return $this->fail('01 alanı var ama devamında en az 21 alanı bekleniyor.');
        }

        if (!str_starts_with($rest, '21')) {
            return $this->fail('01 alanından sonra 21 alanı bekleniyor.');
        }

        [$serial, $after21] = $this->extractVariableAiBody($rest, '21', ['91', '92', '93']);
        if (trim($serial) === '') {
            return $this->fail('21 seri alanı boş.');
        }

        if (str_starts_with($after21, '91')) {
            [$v91, $after91] = $this->extractVariableAiBody($after21, '91', ['92', '93']);
            if (trim($v91) === '') {
                return $this->fail('91 alanı boş.');
            }

            if (!str_starts_with($after91, '92')) {
                return $this->fail("Geçersiz GS1 yapı: 21'den sonra 91 geldi ancak 92 yok. Kısa yapıda 93 kullanılmalı, uzun yapıda ise 91'den sonra 92 gelmelidir.");
            }

            [$v92, $after92] = $this->extractVariableAiBody($after91, '92', []);
            if (trim($v92) === '') {
                return $this->fail('92 alanı boş.');
            }
            if ($after92 !== '') {
                return $this->fail('92 alanından sonra beklenmeyen veri var.');
            }

            return $this->successGs1(
                'gs1_long_01_21_91_92',
                $original,
                '01' . $gtin . '21' . $serial . self::GS . '91' . $v91 . self::GS . '92' . $v92,
                '(01)' . $gtin . '(21)' . $serial . '(91)' . $v91 . '(92)' . $v92
            );
        }

        if (str_starts_with($after21, '93')) {
            [$v93, $after93] = $this->extractVariableAiBody($after21, '93', []);
            if (trim($v93) === '') {
                return $this->fail('93 alanı boş.');
            }
            if ($after93 !== '') {
                return $this->fail('93 alanından sonra beklenmeyen veri var.');
            }

            return $this->successGs1(
                'gs1_short_01_21_93',
                $original,
                '01' . $gtin . '21' . $serial . self::GS . '93' . $v93,
                '(01)' . $gtin . '(21)' . $serial . '(93)' . $v93
            );
        }

        return $this->fail('Geçersiz GS1 yapı: 21 alanından sonra 93 (kısa) veya 91+92 (uzun) bekleniyor.');
    }

    private function successGs1(string $type, string $original, string $normalized, string $aiText): array
    {
        return [
            'success' => true,
            'is_gs1' => true,
            'code_type' => $type,
            'original' => $original,
            'normalized' => self::GS . $normalized,
            'ai_text' => $aiText,
            'error' => null,
        ];
    }

    private function extractVariableAiBody(string $source, string $ai, array $nextPossibleAis): array
    {
        if (!str_starts_with($source, $ai)) {
            return ['', ''];
        }

        $s = substr($source, strlen($ai));
        $gsPos = strpos($s, self::GS);
        if ($gsPos !== false) {
            return [substr($s, 0, $gsPos), substr($s, $gsPos + 1)];
        }

        $minPos = null;
        foreach ($nextPossibleAis as $nextAi) {
            $p = strpos($s, $nextAi);
            if ($p !== false && $p > 0 && ($minPos === null || $p < $minPos)) {
                $minPos = $p;
            }
        }

        if ($minPos !== null) {
            return [substr($s, 0, $minPos), substr($s, $minPos)];
        }

        return [$s, ''];
    }

    private function looksLikeParenthesizedAi(string $s): bool
    {
        return str_contains($s, '(01)')
            || str_contains($s, '(21)')
            || str_contains($s, '(91)')
            || str_contains($s, '(92)')
            || str_contains($s, '(93)');
    }

    private function convertParenthesizedAiToRaw(string $s): string
    {
        return trim(str_replace(['(01)', '(21)', '(91)', '(92)', '(93)'], ['01', '21', '91', '92', '93'], $s));
    }

    private function removeLeadingBomAndInvisible(string $s): string
    {
        return preg_replace('/^(\xEF\xBB\xBF|\xE2\x80\x8B|\x00|\s)+/u', '', $s) ?? $s;
    }

    private function interpretEscapeSequences(string $text): string
    {
        $out = '';
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];
            if ($ch === '\\' && $i + 1 < $len) {
                $next = $text[$i + 1];
                if ($next === 'F') {
                    $out .= self::GS;
                    $i++;
                    continue;
                }
                if ($next === 'n') {
                    $out .= "\n";
                    $i++;
                    continue;
                }
                if ($next === 't') {
                    $out .= "\t";
                    $i++;
                    continue;
                }
                if ($next === '\\') {
                    $out .= '\\';
                    $i++;
                    continue;
                }
            }

            $out .= $ch;
        }

        return $out;
    }

    private function fail(string $message): array
    {
        return [
            'success' => false,
            'is_gs1' => false,
            'code_type' => null,
            'original' => null,
            'normalized' => null,
            'ai_text' => null,
            'error' => $message,
        ];
    }
}

