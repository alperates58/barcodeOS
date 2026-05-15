<?php

namespace App\Services\Barcode;

use App\Models\BarcodeType;
use App\Models\User;
use App\Services\Barcode\Renderers\Contracts\BarcodeRendererInterface;

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
                rendered: null,
                error: $validation['errors'][0] ?? null,
            );
        }

        $rendererClass = $this->barcodeTypeRegistry->rendererClassFor($barcodeType->slug);
        $normalized = $validation['normalized'] ?? [];
        $resolvedFormat = is_string($normalized['format'] ?? null)
            ? strtolower(trim($normalized['format']))
            : null;

        if ($rendererClass === null || $resolvedFormat !== 'svg') {
            return $this->result(
                success: false,
                status: 'renderer_not_supported',
                validation: $validation,
                rendered: null,
                error: [
                    'code' => 'renderer_not_supported',
                    'message' => $rendererClass === null
                        ? 'This barcode type is not supported by the rendering engine yet.'
                        : 'This barcode type preview is only available as SVG in this phase.',
                    'field' => null,
                    'meta' => [
                        'barcode_type_slug' => $barcodeType->slug,
                        'format' => $resolvedFormat,
                    ],
                ],
            );
        }

        /** @var BarcodeRendererInterface $renderer */
        $renderer = app($rendererClass);
        $rendered = $renderer->render(
            (string) ($normalized['data'] ?? ''),
            $resolvedFormat,
            $this->renderParameters($barcodeType, $normalized['parameters'] ?? []),
        );

        return $this->result(
            success: true,
            status: 'rendered',
            validation: $validation,
            rendered: $rendered,
            error: null,
        );
    }

    protected function result(
        bool $success,
        string $status,
        array $validation,
        ?array $rendered,
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
            'rendered' => $rendered,
            'error' => $this->normalizeError($error),
        ];
    }

    protected function renderParameters(BarcodeType $barcodeType, array $parameters): array
    {
        return array_merge([
            'width' => $barcodeType->default_width ?: 300,
            'margin' => $barcodeType->default_margin ?? 1,
        ], $parameters);
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
