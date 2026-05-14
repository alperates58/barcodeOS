<?php

namespace App\Services\Barcode\Renderers\Contracts;

interface BarcodeRendererInterface
{
    public function render(string $data, array $parameters = []): mixed;
}
