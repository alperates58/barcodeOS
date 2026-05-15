<?php

namespace App\Http\Controllers\Barcode;

use App\Http\Controllers\Controller;
use App\Models\BarcodeType;
use App\Services\Barcode\BarcodeGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarcodePreviewController extends Controller
{
    public function __invoke(
        Request $request,
        BarcodeGenerationService $barcodeGenerationService,
    ): JsonResponse {
        $validated = $request->validate([
            'barcode_type_slug' => ['required', 'string', Rule::exists('barcode_types', 'slug')],
            'data' => ['nullable', 'string'],
            'format' => ['nullable', 'string'],
            'parameters' => ['nullable', 'array'],
        ]);

        $barcodeType = BarcodeType::query()
            ->where('slug', $validated['barcode_type_slug'])
            ->firstOrFail();

        return response()->json(
            $barcodeGenerationService->generate(
                $request->user(),
                $barcodeType,
                $validated['data'] ?? null,
                $validated['format'] ?? null,
                $validated['parameters'] ?? [],
            ),
        );
    }
}
