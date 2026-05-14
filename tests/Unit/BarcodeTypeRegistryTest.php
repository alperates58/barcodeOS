<?php

namespace Tests\Unit;

use App\Services\Barcode\BarcodeTypeRegistry;
use Tests\TestCase;

class BarcodeTypeRegistryTest extends TestCase
{
    public function test_unknown_slug_returns_false(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertFalse($registry->hasRendererFor('unknown-slug'));
    }

    public function test_supported_slugs_returns_stable_array(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertSame([
            'qr-code',
            'code-128',
            'code-39',
            'ean-13',
            'upc-a',
        ], $registry->supportedSlugs());
    }

    public function test_renderer_class_for_unknown_slug_returns_null(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertNull($registry->rendererClassFor('unknown-slug'));
    }
}
