<?php

namespace App\Services\Barcode;

use App\Models\BarcodeType;
use App\Models\User;
use App\Services\Usage\UsageLimitService;

class BarcodeValidationService
{
    protected const DEFAULT_RESULT = [
        'data' => null,
        'format' => null,
        'parameters' => [],
    ];

    protected const ACTIVE_STATUSES = [
        'active',
        'enabled',
        'beta',
    ];

    protected const INACTIVE_STATUSES = [
        'inactive',
        'disabled',
        'draft',
        'archived',
    ];

    public function __construct(
        protected BarcodeAccessService $barcodeAccessService,
        protected UsageLimitService $usageLimitService,
        protected Gs1Parser $gs1Parser,
        protected ParameterSchemaResolver $parameterSchemaResolver,
    ) {}

    public function validateBarcodeType(User $user, BarcodeType $barcodeType): array
    {
        if (! $this->isBarcodeTypeActive($barcodeType)) {
            return $this->invalidResult([
                $this->error(
                    'barcode_type_inactive',
                    'This barcode type is not currently available.',
                ),
            ]);
        }

        if (! $this->barcodeAccessService->canUseBarcodeType($user, $barcodeType)) {
            return $this->invalidResult([
                $this->error(
                    'barcode_type_not_allowed',
                    'Your current plan does not allow this barcode type.',
                    meta: [
                        'missing_features' => $this->barcodeAccessService->missingBarcodeTypeFeatures($user, $barcodeType),
                    ],
                ),
            ]);
        }

        return $this->validResult();
    }

    public function validateExportFormat(User $user, BarcodeType $barcodeType, ?string $format): array
    {
        $supportedFormats = $this->supportedFormats($barcodeType);
        $resolvedFormat = $this->resolveRequestedFormat($barcodeType, $format, $supportedFormats);

        if ($resolvedFormat === null) {
            return $this->invalidResult([
                $this->error(
                    'export_format_required',
                    'A supported export format is required.',
                    'format',
                ),
            ]);
        }

        if (! in_array($resolvedFormat, $supportedFormats, true)) {
            return $this->invalidResult([
                $this->error(
                    'export_format_not_supported',
                    'This export format is not supported for the selected barcode type.',
                    'format',
                    ['format' => $resolvedFormat],
                ),
            ], [
                'format' => $resolvedFormat,
            ]);
        }

        if (! $this->barcodeAccessService->canExportFormat($user, $resolvedFormat)) {
            return $this->invalidResult([
                $this->error(
                    'export_format_not_allowed',
                    'Your current plan does not allow this export format.',
                    'format',
                    ['format' => $resolvedFormat],
                ),
            ], [
                'format' => $resolvedFormat,
            ]);
        }

        return $this->validResult([
            'format' => $resolvedFormat,
        ]);
    }

    public function validateData(BarcodeType $barcodeType, ?string $data): array
    {
        $compiledRules = $this->compileDataRules($barcodeType->validation_rules);
        $normalizedData = $this->normalizeData($data, $compiledRules['rules']);
        $errors = $compiledRules['errors'];

        if ($normalizedData === null || $normalizedData === '') {
            $errors[] = $this->error(
                'data_required',
                'Barcode data is required.',
                'data',
            );

            return $this->invalidResult($errors, [
                'data' => $normalizedData,
            ]);
        }

        $length = mb_strlen($normalizedData);
        $rules = $compiledRules['rules'];

        if (isset($rules['min_length']) && $length < $rules['min_length']) {
            $errors[] = $this->error(
                'data_too_short',
                'Barcode data is too short.',
                'data',
                ['min_length' => $rules['min_length']],
            );
        }

        if (isset($rules['max_length']) && $length > $rules['max_length']) {
            $errors[] = $this->error(
                'data_too_long',
                'Barcode data is too long.',
                'data',
                ['max_length' => $rules['max_length']],
            );
        }

        if (isset($rules['exact_length']) && $length !== $rules['exact_length']) {
            $errors[] = $this->error(
                'data_wrong_length',
                'Barcode data length is invalid.',
                'data',
                ['exact_length' => $rules['exact_length']],
            );
        }

        if (($rules['numeric_only'] ?? false) && ! ctype_digit($normalizedData)) {
            $errors[] = $this->error(
                'data_must_be_numeric',
                'Barcode data must contain only numbers.',
                'data',
            );
        }

        if (isset($rules['allowed_regex'])) {
            $regexResult = @preg_match($rules['allowed_regex'], $normalizedData);

            if ($regexResult === false) {
                $errors[] = $this->error(
                    'data_validation_rule_invalid',
                    'A barcode validation rule is misconfigured.',
                    'data',
                    ['rule' => 'allowed_regex'],
                );
            } elseif ($regexResult !== 1) {
                $errors[] = $this->error(
                    'data_invalid_format',
                    'Barcode data format is invalid.',
                    'data',
                );
            }
        }

        if (isset($rules['allowed_characters'])) {
            $invalidCharacters = $this->findInvalidCharacters($normalizedData, $rules['allowed_characters']);

            if ($invalidCharacters !== []) {
                $errors[] = $this->error(
                    'data_contains_invalid_characters',
                    'Barcode data contains invalid characters.',
                    'data',
                    ['invalid_characters' => $invalidCharacters],
                );
            }
        }

        if ($errors !== []) {
            return $this->invalidResult($errors, [
                'data' => $normalizedData,
            ]);
        }

        if ($this->shouldUseGs1Parser($barcodeType, $compiledRules['rules'])) {
            $gs1Result = $this->gs1Parser->parse($normalizedData);

            if (! $gs1Result['valid']) {
                return $this->invalidResult($gs1Result['errors'], [
                    'data' => $normalizedData,
                ]);
            }

            return $this->validResult([
                'data' => $gs1Result['encode_data'],
            ]);
        }

        return $this->validResult([
            'data' => $normalizedData,
        ]);
    }

    public function validateParameters(BarcodeType $barcodeType, array $parameters): array
    {
        $definitions = $this->parameterSchemaResolver->resolveFor($barcodeType);
        $issues = $this->parameterSchemaResolver->issuesFor($barcodeType);
        $errors = array_map(
            fn (array $issue): array => $this->error(
                'parameter_schema_invalid',
                $issue['message'] ?? 'The barcode parameter configuration is invalid.',
                'parameters',
                ['context' => $issue['context'] ?? null],
            ),
            $issues,
        );
        $normalizedParameters = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $field = "parameters.{$key}";
            $hasProvidedValue = array_key_exists($key, $parameters);

            if ($hasProvidedValue) {
                $candidate = $parameters[$key];
            } elseif ($definition['has_default']) {
                $candidate = $definition['default'];
            } else {
                $candidate = null;
            }

            if (! $hasProvidedValue && ! $definition['has_default'] && $definition['required']) {
                $errors[] = $this->error(
                    'parameter_required',
                    'This parameter is required.',
                    $field,
                    ['parameter' => $key],
                );

                continue;
            }

            if (! $hasProvidedValue && ! $definition['has_default']) {
                continue;
            }

            $validation = $this->normalizeParameterValue($definition, $candidate, $field);

            if ($validation['errors'] !== []) {
                $errors = array_merge($errors, $validation['errors']);

                continue;
            }

            $normalizedParameters[$key] = $validation['value'];
        }

        if ($errors !== []) {
            return $this->invalidResult($errors, [
                'parameters' => $normalizedParameters,
            ]);
        }

        return $this->validResult([
            'parameters' => $normalizedParameters,
        ]);
    }

    public function validateGenerationRequest(User $user, BarcodeType $barcodeType, ?string $data, ?string $format, array $parameters = []): array
    {
        $barcodeTypeResult = $this->validateBarcodeType($user, $barcodeType);
        $formatResult = $this->validateExportFormat($user, $barcodeType, $format);
        $dataResult = $this->validateData($barcodeType, $data);
        $parameterResult = $this->validateParameters($barcodeType, $parameters);
        $usageResult = $this->validateUsagePreChecks($user);

        return $this->mergeResults(
            $barcodeTypeResult,
            $formatResult,
            $dataResult,
            $parameterResult,
            $usageResult,
        );
    }

    protected function validateUsagePreChecks(User $user): array
    {
        $errors = [];

        if (! $this->usageLimitService->canUse($user, 'daily_generation_limit')) {
            $errors[] = $this->error(
                'daily_limit_reached',
                'Your daily barcode generation limit has been reached.',
            );
        }

        if (! $this->usageLimitService->canUse($user, 'monthly_generation_limit')) {
            $errors[] = $this->error(
                'monthly_limit_reached',
                'Your monthly barcode generation limit has been reached.',
            );
        }

        if ($errors !== []) {
            return $this->invalidResult($errors);
        }

        return $this->validResult();
    }

    protected function validResult(array $normalized = []): array
    {
        return [
            'valid' => true,
            'errors' => [],
            'normalized' => $this->normalizeResultPayload($normalized),
        ];
    }

    protected function invalidResult(array $errors, array $normalized = []): array
    {
        return [
            'valid' => false,
            'errors' => array_values($errors),
            'normalized' => $this->normalizeResultPayload($normalized),
        ];
    }

    protected function error(string $code, string $message, ?string $field = null, array $meta = []): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'field' => $field,
            'meta' => $meta,
        ];
    }

    protected function mergeResults(array ...$results): array
    {
        $merged = $this->validResult();

        foreach ($results as $result) {
            $merged['valid'] = $merged['valid'] && ($result['valid'] ?? false);
            $merged['errors'] = array_values(array_merge($merged['errors'], $result['errors'] ?? []));

            if (array_key_exists('normalized', $result) && is_array($result['normalized'])) {
                foreach (self::DEFAULT_RESULT as $key => $defaultValue) {
                    if (
                        array_key_exists($key, $result['normalized'])
                        && $result['normalized'][$key] !== $defaultValue
                    ) {
                        $merged['normalized'][$key] = $result['normalized'][$key];
                    }
                }
            }
        }

        return $merged;
    }

    protected function normalizeResultPayload(array $normalized): array
    {
        return [
            'data' => $normalized['data'] ?? self::DEFAULT_RESULT['data'],
            'format' => $normalized['format'] ?? self::DEFAULT_RESULT['format'],
            'parameters' => is_array($normalized['parameters'] ?? null)
                ? $normalized['parameters']
                : self::DEFAULT_RESULT['parameters'],
        ];
    }

    protected function isBarcodeTypeActive(BarcodeType $barcodeType): bool
    {
        $status = strtolower(trim((string) $barcodeType->status));

        if ($status === '') {
            return false;
        }

        if (in_array($status, self::ACTIVE_STATUSES, true)) {
            return true;
        }

        if (in_array($status, self::INACTIVE_STATUSES, true)) {
            return false;
        }

        return false;
    }

    protected function supportedFormats(BarcodeType $barcodeType): array
    {
        $formats = $barcodeType->supported_export_formats ?? [];

        if (! is_array($formats)) {
            return [];
        }

        $normalizedFormats = [];

        foreach ($formats as $format) {
            if (! is_string($format)) {
                continue;
            }

            $normalizedFormat = strtolower(trim($format));

            if ($normalizedFormat === '') {
                continue;
            }

            $normalizedFormats[$normalizedFormat] = $normalizedFormat;
        }

        return array_values($normalizedFormats);
    }

    protected function resolveRequestedFormat(BarcodeType $barcodeType, ?string $format, array $supportedFormats): ?string
    {
        $requestedFormat = is_string($format) ? strtolower(trim($format)) : null;

        if ($requestedFormat !== null && $requestedFormat !== '') {
            return $requestedFormat;
        }

        $defaultFormat = is_string($barcodeType->default_format)
            ? strtolower(trim($barcodeType->default_format))
            : null;

        if ($defaultFormat !== null && $defaultFormat !== '' && in_array($defaultFormat, $supportedFormats, true)) {
            return $defaultFormat;
        }

        if (in_array('png', $supportedFormats, true)) {
            return 'png';
        }

        return null;
    }

    protected function compileDataRules(mixed $rawRules): array
    {
        $compiled = [];
        $errors = [];

        if ($rawRules === null || $rawRules === []) {
            return ['rules' => $compiled, 'errors' => []];
        }

        if (! is_array($rawRules)) {
            return [
                'rules' => $compiled,
                'errors' => [
                    $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                    ),
                ],
            ];
        }

        if ($this->isAssociativeArray($rawRules)) {
            foreach ($rawRules as $key => $value) {
                $normalizedKey = is_string($key) ? strtolower(trim($key)) : null;

                if ($normalizedKey === null || $normalizedKey === '') {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                    );

                    continue;
                }

                $this->applyCompiledDataRule($compiled, $errors, $normalizedKey, $value);
            }

            return ['rules' => $compiled, 'errors' => $errors];
        }

        foreach ($rawRules as $rule) {
            if (! is_string($rule)) {
                $errors[] = $this->error(
                    'data_validation_rule_invalid',
                    'A barcode validation rule is misconfigured.',
                    'data',
                );

                continue;
            }

            $normalizedRule = trim($rule);

            if ($normalizedRule === '') {
                continue;
            }

            [$name, $value] = array_pad(explode(':', $normalizedRule, 2), 2, null);
            $normalizedName = strtolower(trim($name));

            $this->applyCompiledDataRule($compiled, $errors, $normalizedName, $value ?? true);
        }

        return ['rules' => $compiled, 'errors' => $errors];
    }

    protected function applyCompiledDataRule(array &$compiled, array &$errors, string $rule, mixed $value): void
    {
        switch ($rule) {
            case 'required':
                $compiled['required'] = true;

                return;

            case 'min':
            case 'min_length':
                $parsedInteger = $this->parseInteger($value);

                if ($parsedInteger === null) {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => $rule],
                    );

                    return;
                }

                $compiled['min_length'] = $parsedInteger;

                return;

            case 'max':
            case 'max_length':
                $parsedInteger = $this->parseInteger($value);

                if ($parsedInteger === null) {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => $rule],
                    );

                    return;
                }

                $compiled['max_length'] = $parsedInteger;

                return;

            case 'size':
            case 'exact_length':
                $parsedInteger = $this->parseInteger($value);

                if ($parsedInteger === null) {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => $rule],
                    );

                    return;
                }

                $compiled['exact_length'] = $parsedInteger;

                return;

            case 'digits':
                $parsedInteger = $this->parseInteger($value);

                if ($parsedInteger === null) {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => $rule],
                    );

                    return;
                }

                $compiled['exact_length'] = $parsedInteger;
                $compiled['numeric_only'] = true;

                return;

            case 'numeric':
            case 'numeric_only':
                $compiled['numeric_only'] = $this->coerceBoolean($value) ?? true;

                return;

            case 'regex':
            case 'allowed_regex':
                if (! is_string($value) || trim($value) === '') {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => 'allowed_regex'],
                    );

                    return;
                }

                $compiled['allowed_regex'] = trim($value);

                return;

            case 'allowed_characters':
                if (! is_string($value) || $value === '') {
                    $errors[] = $this->error(
                        'data_validation_rule_invalid',
                        'A barcode validation rule is misconfigured.',
                        'data',
                        ['rule' => 'allowed_characters'],
                    );

                    return;
                }

                $compiled['allowed_characters'] = $value;

                return;

            case 'preserve_whitespace':
                $compiled['preserve_whitespace'] = $this->coerceBoolean($value) ?? true;

                return;

            case 'gs1_datamatrix':
                $compiled['gs1_datamatrix'] = $this->coerceBoolean($value) ?? true;

                return;

            default:
                $errors[] = $this->error(
                    'data_validation_rule_invalid',
                    'A barcode validation rule is misconfigured.',
                    'data',
                    ['rule' => $rule],
                );
        }
    }

    protected function normalizeData(?string $data, array $rules): ?string
    {
        if ($data === null) {
            return null;
        }

        if ($rules['preserve_whitespace'] ?? false) {
            return $data;
        }

        return trim($data);
    }

    protected function shouldUseGs1Parser(BarcodeType $barcodeType, array $rules): bool
    {
        $slug = strtolower(trim((string) $barcodeType->slug));

        if (in_array($slug, ['gs1-datamatrix', 'gs1-data-matrix'], true)) {
            return true;
        }

        if (! in_array($slug, ['data-matrix', 'datamatrix'], true)) {
            return false;
        }

        return (bool) ($rules['gs1_datamatrix'] ?? false);
    }

    protected function findInvalidCharacters(string $value, string $allowedCharacters): array
    {
        $allowedLookup = [];

        foreach (mb_str_split($allowedCharacters) as $allowedCharacter) {
            $allowedLookup[$allowedCharacter] = true;
        }

        $invalidCharacters = [];

        foreach (mb_str_split($value) as $character) {
            if (! isset($allowedLookup[$character])) {
                $invalidCharacters[$character] = $character;
            }
        }

        return array_values($invalidCharacters);
    }

    protected function normalizeParameterValue(array $definition, mixed $value, string $field): array
    {
        return match ($definition['type']) {
            'text' => $this->normalizeTextParameter($value),
            'integer' => $this->normalizeIntegerParameter($definition, $value, $field),
            'number' => $this->normalizeNumberParameter($definition, $value, $field),
            'boolean' => $this->normalizeBooleanParameter($value, $field),
            'select' => $this->normalizeSelectParameter($definition, $value, $field),
            'multi_select' => $this->normalizeMultiSelectParameter($definition, $value, $field),
            'color' => $this->normalizeColorParameter($value, $field),
            default => [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_schema_invalid',
                        'The barcode parameter configuration is invalid.',
                        $field,
                    ),
                ],
            ],
        };
    }

    protected function normalizeTextParameter(mixed $value): array
    {
        if ($value === null) {
            return ['value' => '', 'errors' => []];
        }

        if (is_array($value) || is_object($value)) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_schema_invalid',
                        'The barcode parameter configuration is invalid.',
                    ),
                ],
            ];
        }

        return [
            'value' => (string) $value,
            'errors' => [],
        ];
    }

    protected function normalizeIntegerParameter(array $definition, mixed $value, string $field): array
    {
        if (! is_numeric($value) || (string) (int) $value !== (string) $value && (string) $value !== (string) floor((float) $value)) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_invalid_number',
                        'This parameter must be a valid integer.',
                        $field,
                    ),
                ],
            ];
        }

        $normalizedValue = (int) $value;

        return $this->applyNumericBounds($definition, $normalizedValue, $field);
    }

    protected function normalizeNumberParameter(array $definition, mixed $value, string $field): array
    {
        if (! is_numeric($value)) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_invalid_number',
                        'This parameter must be a valid number.',
                        $field,
                    ),
                ],
            ];
        }

        $normalizedValue = str_contains((string) $value, '.') ? (float) $value : (int) $value;

        return $this->applyNumericBounds($definition, $normalizedValue, $field);
    }

    protected function applyNumericBounds(array $definition, int|float $value, string $field): array
    {
        $errors = [];
        $minimum = $this->parseFloat($definition['min']);
        $maximum = $this->parseFloat($definition['max']);

        if ($minimum !== null && $value < $minimum) {
            $errors[] = $this->error(
                'parameter_too_low',
                'This parameter value is too low.',
                $field,
                ['min' => $minimum],
            );
        }

        if ($maximum !== null && $value > $maximum) {
            $errors[] = $this->error(
                'parameter_too_high',
                'This parameter value is too high.',
                $field,
                ['max' => $maximum],
            );
        }

        return [
            'value' => $value,
            'errors' => $errors,
        ];
    }

    protected function normalizeBooleanParameter(mixed $value, string $field): array
    {
        if (is_bool($value)) {
            return ['value' => $value, 'errors' => []];
        }

        if (is_int($value) || is_string($value)) {
            $normalizedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($normalizedValue !== null) {
                return ['value' => $normalizedValue, 'errors' => []];
            }
        }

        return [
            'value' => null,
            'errors' => [
                $this->error(
                    'parameter_invalid_boolean',
                    'This parameter must be true or false.',
                    $field,
                ),
            ],
        ];
    }

    protected function normalizeSelectParameter(array $definition, mixed $value, string $field): array
    {
        $normalizedValue = is_string($value) || is_int($value) || is_float($value) || is_bool($value)
            ? (string) $value
            : null;

        if ($normalizedValue === null || ! in_array($normalizedValue, $definition['options'], true)) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_invalid_option',
                        'This parameter contains an invalid option.',
                        $field,
                    ),
                ],
            ];
        }

        return ['value' => $normalizedValue, 'errors' => []];
    }

    protected function normalizeMultiSelectParameter(array $definition, mixed $value, string $field): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalizedValues = [];
        $invalid = false;

        foreach ($values as $item) {
            if (! is_string($item) && ! is_int($item) && ! is_float($item) && ! is_bool($item)) {
                $invalid = true;

                break;
            }

            $normalizedItem = (string) $item;

            if (! in_array($normalizedItem, $definition['options'], true)) {
                $invalid = true;

                break;
            }

            $normalizedValues[] = $normalizedItem;
        }

        if ($invalid) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_invalid_option',
                        'This parameter contains an invalid option.',
                        $field,
                    ),
                ],
            ];
        }

        return [
            'value' => array_values(array_unique($normalizedValues)),
            'errors' => [],
        ];
    }

    protected function normalizeColorParameter(mixed $value, string $field): array
    {
        $normalizedValue = is_string($value) ? trim($value) : null;

        if ($normalizedValue === null || ! preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $normalizedValue)) {
            return [
                'value' => null,
                'errors' => [
                    $this->error(
                        'parameter_invalid_color',
                        'This parameter must be a valid hex color.',
                        $field,
                    ),
                ],
            ];
        }

        return ['value' => $normalizedValue, 'errors' => []];
    }

    protected function isSupportedParameterType(string $type): bool
    {
        return in_array($type, ['text', 'number', 'integer', 'boolean', 'select', 'multi_select', 'color'], true);
    }

    protected function isAssociativeArray(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    protected function parseInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return null;
    }

    protected function parseFloat(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric(trim($value))) {
            return (float) trim($value);
        }

        return null;
    }

    protected function coerceBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return null;
    }
}
