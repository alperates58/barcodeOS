<?php

namespace App\Http\Controllers\Barcode;

use App\Http\Controllers\Controller;
use App\Models\BarcodeType;
use App\Models\User;
use App\Services\Barcode\BarcodeAccessService;
use App\Services\Barcode\ParameterSchemaResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarcodeTypeConfigController extends Controller
{
    protected const ACTIVE_STATUSES = [
        'active',
        'enabled',
        'beta',
    ];

    public function __invoke(
        Request $request,
        BarcodeType $barcodeType,
        ParameterSchemaResolver $parameterSchemaResolver,
        BarcodeAccessService $barcodeAccessService,
    ): JsonResponse {
        abort_unless($this->isActive($barcodeType), 404);

        $barcodeType->loadMissing('category');

        /** @var User $user */
        $user = $request->user();
        $supportedFormats = is_array($barcodeType->supported_export_formats)
            ? $barcodeType->supported_export_formats
            : [];

        $normalizedFormats = [];

        foreach ($supportedFormats as $format) {
            if (! is_string($format)) {
                continue;
            }

            $normalizedFormat = strtolower(trim($format));

            if ($normalizedFormat === '') {
                continue;
            }

            $featureKey = $barcodeAccessService->exportFeatureKey($normalizedFormat);

            $normalizedFormats[$normalizedFormat] = [
                'format' => $normalizedFormat,
                'allowed' => $featureKey !== null
                    ? $barcodeAccessService->canExportFormat($user, $normalizedFormat)
                    : false,
                'feature_key' => $featureKey,
            ];
        }

        return response()->json([
            'id' => $barcodeType->id,
            'name' => $barcodeType->name,
            'slug' => $barcodeType->slug,
            'description' => $barcodeType->description,
            'category' => $barcodeType->category ? [
                'id' => $barcodeType->category->id,
                'name' => $barcodeType->category->name,
                'slug' => $barcodeType->category->slug,
            ] : null,
            'example_value' => $barcodeType->example_value,
            'default_format' => $barcodeType->default_format,
            'supported_export_formats' => array_values(array_keys($normalizedFormats)),
            'required_features' => is_array($barcodeType->required_features)
                ? array_values($barcodeType->required_features)
                : [],
            'parameter_schema' => $parameterSchemaResolver->resolveFor($barcodeType),
            'validation_rules' => is_array($barcodeType->validation_rules)
                ? $barcodeType->validation_rules
                : [],
            'access' => [
                'can_use' => $barcodeAccessService->canUseBarcodeType($user, $barcodeType),
                'missing_features' => $barcodeAccessService->missingBarcodeTypeFeatures($user, $barcodeType),
            ],
            'export_formats' => array_values($normalizedFormats),
        ]);
    }

    protected function isActive(BarcodeType $barcodeType): bool
    {
        $status = strtolower(trim((string) $barcodeType->status));

        return in_array($status, self::ACTIVE_STATUSES, true);
    }
}
