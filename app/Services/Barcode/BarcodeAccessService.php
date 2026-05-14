<?php

namespace App\Services\Barcode;

use App\Models\BarcodeType;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;

class BarcodeAccessService
{
    protected const EXPORT_FEATURE_MAP = [
        'png' => 'export.png',
        'svg' => 'export.svg',
        'pdf' => 'export.pdf',
        'eps' => 'export.eps',
        'zip' => 'export.zip',
    ];

    public function __construct(
        protected EntitlementService $entitlementService,
    ) {}

    public function canUseBarcodeType(User $user, BarcodeType $barcodeType): bool
    {
        return $this->missingBarcodeTypeFeatures($user, $barcodeType) === [];
    }

    public function missingBarcodeTypeFeatures(User $user, BarcodeType $barcodeType): array
    {
        $requiredFeatures = $this->requiredBarcodeTypeFeatures($barcodeType);

        return array_values(array_filter(
            $requiredFeatures,
            fn (string $featureKey): bool => $this->entitlementService->cannot($user, $featureKey),
        ));
    }

    public function canExportFormat(User $user, string $format): bool
    {
        $featureKey = $this->exportFeatureKey($format);

        if ($featureKey === null) {
            return false;
        }

        return $this->entitlementService->allows($user, $featureKey);
    }

    public function exportFeatureKey(string $format): ?string
    {
        return self::EXPORT_FEATURE_MAP[strtolower(trim($format))] ?? null;
    }

    protected function requiredBarcodeTypeFeatures(BarcodeType $barcodeType): array
    {
        $requiredFeatures = $barcodeType->required_features ?? [];

        if (! is_array($requiredFeatures)) {
            $requiredFeatures = [];
        }

        return array_values(array_unique([
            'barcode.generate',
            ...array_filter($requiredFeatures, fn (mixed $featureKey): bool => is_string($featureKey) && $featureKey !== ''),
        ]));
    }
}
