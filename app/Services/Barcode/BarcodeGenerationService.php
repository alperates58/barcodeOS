<?php

namespace App\Services\Barcode;

use App\Models\BarcodeType;
use App\Models\User;

class BarcodeGenerationService
{
    public function __construct(
        protected BarcodeValidationService $barcodeValidationService,
        protected BarcodeTypeRegistry $barcodeTypeRegistry,
    ) {}

    public function generate(
        User $user,
        BarcodeType $barcodeType,
        ?string $data,
        ?string $format,
        array $parameters = [],
    ): array {
        $validation = $this->barcodeValidationService->validateGenerationRequest(
            $user,
            $barcodeType,
            $data,
            $format,
            $parameters,
        );

        if (! ($validation['valid'] ?? false)) {
            return $this->result(
                success: false,
                status: 'validation_failed',
                validation: $validation,
                error: $validation['errors'][0] ?? null,
            );
        }

        if (! $this->barcodeTypeRegistry->hasRendererFor($barcodeType->slug)) {
            return $this->result(
                success: false,
                status: 'renderer_not_supported',
                validation: $validation,
                error: [
                    'code' => 'renderer_not_supported',
                    'message' => 'This barcode type is not supported by the rendering engine yet.',
                    'field' => null,
                    'meta' => [
                        'barcode_type_slug' => $barcodeType->slug,
                    ],
                ],
            );
        }

        return $this->result(
            success: true,
            status: 'ready_for_render',
            validation: $validation,
            error: null,
        );
    }

    protected function result(
        bool $success,
        string $status,
        array $validation,
        ?array $error,
    ): array {
        return [
            'success' => $success,
            'status' => $status,
            'validation' => $validation,
            'normalized' => $validation['normalized'] ?? [
                'data' => null,
                'format' => null,
                'parameters' => [],
            ],
            'error' => $this->normalizeError($error),
        ];
    }

    protected function normalizeError(?array $error): ?array
    {
        if ($error === null) {
            return null;
        }

        return [
            'code' => $error['code'] ?? 'unknown_error',
            'message' => $error['message'] ?? 'An unknown barcode generation error occurred.',
            'field' => $error['field'] ?? null,
            'meta' => is_array($error['meta'] ?? null) ? $error['meta'] : [],
        ];
    }
}
