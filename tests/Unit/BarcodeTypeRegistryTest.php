<?php

namespace Tests\Unit;

use App\Services\Barcode\Renderers\TwoD\QrCodeRenderer;
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

        $this->assertSame(['qr-code'], $registry->supportedSlugs());
    }

    public function test_renderer_class_for_unknown_slug_returns_null(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertNull($registry->rendererClassFor('unknown-slug'));
    }

    public function test_qr_code_maps_to_qr_code_renderer(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertTrue($registry->hasRendererFor('qr-code'));
        $this->assertSame(QrCodeRenderer::class, $registry->rendererClassFor('qr-code'));
    }

    public function test_rendererless_slug_remains_unsupported(): void
    {
        $registry = app(BarcodeTypeRegistry::class);

        $this->assertFalse($registry->hasRendererFor('code-128'));
        $this->assertNull($registry->rendererClassFor('code-128'));
    }
}
