<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantDashboardService;
use Illuminate\Contracts\View\View;

class MerchantDashboardController extends Controller
{
    /**
     * Display a listing of all registered merchants with direct dashboard switch options.
     */
    public function index(): View
    {
        $merchants = Merchant::query()
            ->with(['plans' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['customers', 'plans'])
            ->orderBy('name')
            ->get();

        return view('merchants.index', compact('merchants'));
    }

    /**
     * Display the visual merchant dashboard view.
     */
    public function show(Merchant $merchant, MerchantDashboardService $service): View
    {
        $metrics = $service->getDashboardMetrics($merchant);
        $allMerchants = Merchant::query()
            ->select(['id', 'name', 'slug', 'currency'])
            ->orderBy('name')
            ->get();

        return view('merchants.dashboard', array_merge($metrics, [
            'current_merchant' => $merchant,
            'all_merchants' => $allMerchants,
        ]));
    }
}
