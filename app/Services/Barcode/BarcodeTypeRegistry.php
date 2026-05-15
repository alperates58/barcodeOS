<?php

namespace App\Services\Barcode;

class BarcodeTypeRegistry
{
    protected const RENDERER_MAP = [
        'qr-code' => 'App\\Services\\Barcode\\Renderers\\TwoD\\QrCodeRenderer',
    ];

    public function supportedSlugs(): array
    {
        return array_keys(self::RENDERER_MAP);
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

        $rendererClass = self::RENDERER_MAP[$normalizedSlug] ?? null;

        if ($rendererClass === null || ! class_exists($rendererClass)) {
            return null;
        }

        return $rendererClass;
    }
}
