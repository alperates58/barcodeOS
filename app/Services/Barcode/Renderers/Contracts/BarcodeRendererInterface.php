<?php

namespace App\Services\Barcode\Renderers\Contracts;

interface BarcodeRendererInterface
{
    public function render(string $data, string $format, array $parameters = []): array;
}
