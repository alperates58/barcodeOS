<?php

namespace App\Services\Barcode;

use App\Models\BarcodeParameter;
use App\Models\BarcodeType;

class ParameterSchemaResolver
{
    protected const SUPPORTED_TYPES = [
        'text',
        'number',
        'integer',
        'boolean',
        'select',
        'multi_select',
        'color',
    ];

    public function resolveFor(BarcodeType $barcodeType): array
    {
        return $this->compiledFor($barcodeType)['parameters'];
    }

    public function issuesFor(BarcodeType $barcodeType): array
    {
        return $this->compiledFor($barcodeType)['issues'];
    }

    protected function compiledFor(BarcodeType $barcodeType): array
    {
        $parameters = [];
        $issues = [];
        $schema = $barcodeType->parameter_schema;

        if ($schema !== null && $schema !== []) {
            if (! is_array($schema)) {
                $issues[] = $this->issue('schema', 'The barcode parameter configuration is invalid.');
            } else {
                foreach ($schema as $index => $definition) {
                    $normalized = $this->normalizeSchemaDefinition($definition, "schema.{$index}");

                    if ($normalized === null) {
                        $issues[] = $this->issue("schema.{$index}", 'The barcode parameter configuration is invalid.');

                        continue;
                    }

                    $parameters[$normalized['key']] = $normalized;
                }
            }
        }

        $activeParameters = $barcodeType->relationLoaded('parameters')
            ? $barcodeType->parameters->where('is_active', true)->sortBy('sort_order')
            : $barcodeType->parameters()->where('is_active', true)->orderBy('sort_order')->get();

        foreach ($activeParameters as $parameter) {
            $normalized = $this->normalizeBarcodeParameter($parameter);

            if ($normalized === null) {
                $issues[] = $this->issue("parameter.{$parameter->key}", 'The barcode parameter configuration is invalid.');

                continue;
            }

            if (array_key_exists($normalized['key'], $parameters)) {
                continue;
            }

            $parameters[$normalized['key']] = $normalized;
        }

        return [
            'parameters' => array_values($parameters),
            'issues' => $issues,
        ];
    }

    protected function normalizeSchemaDefinition(mixed $definition, string $context): ?array
    {
        if (! is_array($definition)) {
            return null;
        }

        $key = $this->normalizeKey($definition['key'] ?? null);
        $type = $this->normalizeType($definition['type'] ?? null);

        if ($key === null || $type === null) {
            return null;
        }

        $hasDefault = array_key_exists('default', $definition);

        return [
            'key' => $key,
            'label' => $this->normalizeLabel($definition['label'] ?? null, $key),
            'type' => $type,
            'required' => (bool) ($definition['required'] ?? false),
            'default' => $hasDefault ? $definition['default'] : null,
            'has_default' => $hasDefault,
            'min' => $definition['min'] ?? null,
            'max' => $definition['max'] ?? null,
            'options' => $this->normalizeOptions($definition['options'] ?? null),
            'help_text' => $this->normalizeNullableString($definition['help_text'] ?? null),
            'available_features' => $this->normalizeFeatureKeys($definition['available_features'] ?? null),
            'sort_order' => $this->normalizeSortOrder($definition['sort_order'] ?? null),
            'source' => $context,
        ];
    }

    protected function normalizeBarcodeParameter(BarcodeParameter $parameter): ?array
    {
        $key = $this->normalizeKey($parameter->key);
        $type = $this->normalizeType($parameter->type);

        if ($key === null || $type === null) {
            return null;
        }

        $hasDefault = $parameter->default_value !== null && $parameter->default_value !== '';

        return [
            'key' => $key,
            'label' => $this->normalizeLabel($parameter->label, $key),
            'type' => $type,
            'required' => (bool) $parameter->is_required,
            'default' => $hasDefault ? $parameter->default_value : null,
            'has_default' => $hasDefault,
            'min' => $parameter->min_value,
            'max' => $parameter->max_value,
            'options' => $this->normalizeOptions($parameter->options),
            'help_text' => $this->normalizeNullableString($parameter->help_text),
            'available_features' => $this->normalizeFeatureKeys($parameter->available_features),
            'sort_order' => $this->normalizeSortOrder($parameter->sort_order),
            'source' => 'barcode_parameter',
        ];
    }

    protected function normalizeKey(mixed $key): ?string
    {
        if (! is_string($key)) {
            return null;
        }

        $normalizedKey = trim($key);

        return $normalizedKey === '' ? null : $normalizedKey;
    }

    protected function normalizeType(mixed $type): ?string
    {
        if (! is_string($type)) {
            return null;
        }

        $normalizedType = strtolower(trim($type));

        return in_array($normalizedType, self::SUPPORTED_TYPES, true)
            ? $normalizedType
            : null;
    }

    protected function normalizeLabel(mixed $label, string $fallback): string
    {
        $normalizedLabel = $this->normalizeNullableString($label);

        return $normalizedLabel ?? $fallback;
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalizedValue = trim($value);

        return $normalizedValue === '' ? null : $normalizedValue;
    }

    protected function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $normalizedOptions = [];

        foreach ($options as $option) {
            if (is_array($option)) {
                $candidate = $option['value'] ?? $option['label'] ?? null;

                if (is_string($candidate) || is_int($candidate) || is_float($candidate) || is_bool($candidate)) {
                    $normalizedOptions[] = (string) $candidate;
                }

                continue;
            }

            if (is_string($option) || is_int($option) || is_float($option) || is_bool($option)) {
                $normalizedOptions[] = (string) $option;
            }
        }

        return array_values(array_unique(array_filter(
            array_map('trim', $normalizedOptions),
            fn (string $option): bool => $option !== '',
        )));
    }

    protected function normalizeFeatureKeys(mixed $featureKeys): array
    {
        if (! is_array($featureKeys)) {
            return [];
        }

        $normalized = [];

        foreach ($featureKeys as $featureKey) {
            if (! is_string($featureKey)) {
                continue;
            }

            $candidate = trim($featureKey);

            if ($candidate === '') {
                continue;
            }

            $normalized[] = $candidate;
        }

        return array_values(array_unique($normalized));
    }

    protected function normalizeSortOrder(mixed $sortOrder): int
    {
        if (is_int($sortOrder)) {
            return $sortOrder;
        }

        if (is_string($sortOrder) && preg_match('/^-?\d+$/', trim($sortOrder)) === 1) {
            return (int) trim($sortOrder);
        }

        return 0;
    }

    protected function issue(string $context, string $message): array
    {
        return [
            'context' => $context,
            'message' => $message,
        ];
    }
}
