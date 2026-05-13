<?php

namespace App\Http\Controllers;

use App\Models\GeneratedBarcode;
use App\Services\Plans\PlanResolverService;
use App\Services\Usage\UsageLimitService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PlanResolverService $planResolverService,
        UsageLimitService $usageLimitService,
    ): Response {
        $user = $request->user();
        $plan = $planResolverService->currentPlanFor($user);
        $usage = $usageLimitService->summary($user);

        $recentBarcodes = GeneratedBarcode::query()
            ->with('barcodeType')
            ->whereBelongsTo($user)
            ->latest('generated_at')
            ->limit(5)
            ->get()
            ->map(fn (GeneratedBarcode $barcode): array => [
                'id' => $barcode->id,
                'barcode_type' => $barcode->barcodeType?->name,
                'export_format' => strtoupper($barcode->export_format),
                'status' => $barcode->status,
                'generated_at' => optional($barcode->generated_at)->toDateTimeString(),
            ])
            ->values();

        return Inertia::render('dashboard', [
            'currentPlan' => $plan ? [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'trial_days' => $plan->trial_days,
                'currency' => $plan->currency,
            ] : null,
            'usageSummary' => $usage,
            'recentBarcodes' => $recentBarcodes,
        ]);
    }
}
