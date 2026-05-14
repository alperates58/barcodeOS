<?php

namespace App\Services\Barcode;

class BarcodeTypeRegistry
{
    protected const FUTURE_RENDERER_MAP = [
        'qr-code' => 'App\\Services\\Barcode\\Renderers\\TwoD\\QrCodeRenderer',
        'code-128' => 'App\\Services\\Barcode\\Renderers\\OneD\\Code128Renderer',
        'code-39' => 'App\\Services\\Barcode\\Renderers\\OneD\\Code39Renderer',
        'ean-13' => 'App\\Services\\Barcode\\Renderers\\Retail\\Ean13Renderer',
        'upc-a' => 'App\\Services\\Barcode\\Renderers\\Retail\\UpcARenderer',
    ];

    public function supportedSlugs(): array
    {
        return array_keys(self::FUTURE_RENDERER_MAP);
    }

    public function hasRendererFor(string $slug): bool
    {
        $rendererClass = $this->rendererClassFor($slug);

        return $rendererClass !== null && class_exists($rendererClass);
    }

    public function rendererClassFor(string $slug): ?string
    {
        $normalizedSlug = strtolower(trim($slug));

        if ($normalizedSlug === '') {
            return null;
        }

        $rendererClass = self::FUTURE_RENDERER_MAP[$normalizedSlug] ?? null;

        if ($rendererClass === null || ! class_exists($rendererClass)) {
            return null;
        }

        return $rendererClass;
    }
}
