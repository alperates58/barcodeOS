<?php

namespace App\Services\Barcode\Validators\Contracts;

interface BarcodeValidatorInterface
{
    public function validate(string $data, array $parameters = []): array;
}
