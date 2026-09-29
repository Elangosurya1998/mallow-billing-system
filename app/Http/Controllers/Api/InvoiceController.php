<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Invoicing\ProcessPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): AnonymousResourceCollection
    {
        $tenantId = $tenantContext->getTenantId()
            ?? $request->header('X-Tenant-ID')
            ?? (string) $request->input('tenant_id')
            ?? (string) $request->input('merchant_id');

        $query = Invoice::withoutGlobalScopes()
            ->with(['items', 'customer'])
            ->where('merchant_id', $tenantId)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->query('subscription_id'));
        }

        $limit = min(max((int) $request->query('limit', 20), 1), 100);

        return InvoiceResource::collection($query->paginate($limit));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice->load('items'));
    }

    public function pay(Invoice $invoice, ProcessPaymentAction $action): JsonResponse
    {
        $result = $action->execute($invoice);

        return response()->json([
            'success' => $result->success,
            'transaction_id' => $result->transactionId,
            'amount_cents' => $result->amountCents,
            'failure_reason' => $result->failureReason,
            'invoice' => new InvoiceResource($invoice->fresh(['items'])),
        ]);
    }
}
