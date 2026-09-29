<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Merchants\GetMerchantDashboardAction;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantDashboardController extends Controller
{
    /**
     * Retrieve aggregated operational and revenue analytics for a merchant.
     */
    public function show(string $id, Request $request, GetMerchantDashboardAction $action): JsonResponse
    {
        /** @var Merchant $merchant */
        $merchant = Merchant::query()->findOrFail($id);

        $asOf = $request->query('as_of')
            ? Carbon::parse((string) $request->query('as_of'))
            : null;

        $dashboard = $action->execute($merchant, $asOf);

        return response()->json([
            'data' => $dashboard->toArray(),
        ]);
    }
}
