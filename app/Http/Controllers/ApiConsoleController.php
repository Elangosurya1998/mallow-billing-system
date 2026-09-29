<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ApiConsoleController extends Controller
{
    /**
     * Display the interactive API web console and submission playground.
     */
    public function index(Request $request): View
    {
        $merchants = Merchant::query()
            ->with([
                'customers' => fn ($q) => $q->orderBy('name'),
                'plans' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'subscriptions' => fn ($q) => $q->with(['customer', 'plan', 'periods'])->where('status', 'active'),
                'invoices' => fn ($q) => $q->latest()->limit(20),
            ])
            ->orderBy('name')
            ->get();

        $merchantId = $request->query('merchant_id');

        $selectedMerchant = $merchants->firstWhere('id', $merchantId)
            ?? $merchants->firstWhere('slug', 'acme-corp')
            ?? $merchants->first();

        return view('console.index', [
            'merchants' => $merchants,
            'selected_merchant' => $selectedMerchant,
        ]);
    }

    /**
     * Display the interactive console pre-scoped to a specific merchant.
     */
    public function merchantConsole(Merchant $merchant): View
    {
        $merchant->load([
            'customers' => fn ($q) => $q->orderBy('name'),
            'plans' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
            'subscriptions' => fn ($q) => $q->with(['customer', 'plan', 'periods'])->where('status', 'active'),
            'invoices' => fn ($q) => $q->latest()->limit(20),
        ]);

        $merchants = Merchant::query()->select(['id', 'name', 'slug', 'currency'])->orderBy('name')->get();

        return view('console.index', [
            'merchants' => $merchants,
            'selected_merchant' => $merchant,
        ]);
    }
}
