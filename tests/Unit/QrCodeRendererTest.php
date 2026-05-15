<?php

namespace Tests\Unit;

use App\Models\BarcodeExport;
use App\Models\GeneratedBarcode;
use App\Models\UsageCounter;
use App\Services\Barcode\Renderers\TwoD\QrCodeRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_svg_for_valid_data(): void
    {
        $renderer = app(QrCodeRenderer::class);

        $result = $renderer->render('https://barcodeos.com', 'svg', [
            'width' => 300,
            'margin' => 1,
        ]);

        $this->assertSame('svg', $result['format']);
        $this->assertSame('image/svg+xml', $result['mime_type']);
        $this->assertStringContainsString('<svg', $result['content']);
        $this->assertSame(300, $result['width']);
        $this->assertSame(300, $result['height']);
        $this->assertSame('qr-code', $result['metadata']['renderer']);
    }

    public function test_it_has_no_side_effects(): void
    {
        $renderer = app(QrCodeRenderer::class);

        $renderer->render('HELLO-42', 'svg');

        $this->assertSame(0, UsageCounter::query()->count());
        $this->assertSame(0, GeneratedBarcode::query()->count());
        $this->assertSame(0, BarcodeExport::query()->count());
    }
}
