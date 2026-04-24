<?php

declare(strict_types=1);

namespace Dmc\Services;

final class InputParser
{
    public function fromRequest(array $files, array $post): array
    {
        $text = trim((string)($post['input_text'] ?? ''));
        $sourceType = 'paste';

        if (isset($files['input_file']) && is_uploaded_file($files['input_file']['tmp_name'])) {
            $text = (string)file_get_contents($files['input_file']['tmp_name']);
            $sourceType = 'file';
        }

        $mode = (string)($post['input_mode'] ?? 'line');
        return [$this->parseText($text, $mode), $sourceType];
    }

    public function parseText(string $text, string $mode = 'line'): array
    {
        $text = $this->decodeText($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $items = [];

        foreach ($lines as $index => $line) {
            $lineNo = $index + 1;
            $value = rtrim($line, "\r\n");

            if ($mode === 'csv_first_column') {
                $columns = str_getcsv($value);
                $value = isset($columns[0]) ? trim((string)$columns[0]) : '';
            }

            if (trim($value) === '') {
                continue;
            }

            $items[] = [
                'line_no' => $lineNo,
                'value' => $value,
            ];
        }

        return $items;
    }

    private function decodeText(string $text): string
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            return substr($text, 3);
        }

        if (str_starts_with($text, "\xFF\xFE")) {
            return mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($text, "\xFE\xFF")) {
            return mb_convert_encoding(substr($text, 2), 'UTF-8', 'UTF-16BE');
        }

        return $text;
    }
}

