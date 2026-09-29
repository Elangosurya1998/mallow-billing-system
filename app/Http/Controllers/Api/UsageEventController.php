<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Ingestion\BatchIngestUsageEventsAction;
use App\Actions\Ingestion\IngestUsageEventAction;
use App\DTOs\UsageBatchDto;
use App\DTOs\UsageEventDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchUsageEventRequest;
use App\Http\Requests\StoreUsageEventRequest;
use App\Http\Resources\UsageEventResource;
use App\Http\Resources\UsageSummaryResource;
use App\Models\DailyUsageSummary;
use App\Models\UsageEvent;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class UsageEventController extends Controller
{
    public function store(
        StoreUsageEventRequest $request,
        IngestUsageEventAction $action,
        TenantContext $tenantContext,
    ): JsonResponse {
        $tenantId = $tenantContext->getTenantId() ?? (string) $request->input('tenant_id');

        $dto = UsageEventDto::fromArray($request->validated(), $tenantId);
        $event = $action->execute($dto);

        return (new UsageEventResource($event))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function batch(
        BatchUsageEventRequest $request,
        BatchIngestUsageEventsAction $action,
        TenantContext $tenantContext,
    ): JsonResponse {
        $tenantId = $tenantContext->getTenantId() ?? (string) $request->input('tenant_id');

        $batchDto = UsageBatchDto::fromArray($request->validated('events'), $tenantId);
        $result = $action->execute($batchDto);

        return response()->json([
            'status' => 'processed',
            'ingested' => $result['ingested'],
            'duplicates' => $result['duplicates'],
            'total_quantity' => $result['total_quantity'],
        ], Response::HTTP_ACCEPTED);
    }

    public function summary(Request $request, TenantContext $tenantContext): AnonymousResourceCollection
    {
        $tenantId = $tenantContext->getTenantId() ?? $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

        $query = DailyUsageSummary::query();

        if ($tenantId) {
            $query->where('merchant_id', $tenantId);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('metric')) {
            $query->where('metric_identifier', $request->query('metric'));
        }

        if ($request->has('metric_identifier')) {
            $query->where('metric_identifier', $request->query('metric_identifier'));
        }

        if ($request->has('start_date')) {
            $query->where('usage_date', '>=', $request->query('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('usage_date', '<=', $request->query('end_date'));
        }

        $summaries = $query->orderBy('usage_date', 'desc')->get();

        return UsageSummaryResource::collection($summaries);
    }

    public function index(Request $request, TenantContext $tenantContext): AnonymousResourceCollection
    {
        $tenantId = $tenantContext->getTenantId() ?? $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

        $query = UsageEvent::query();

        if ($tenantId) {
            $query->where('merchant_id', $tenantId);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->query('customer_id'));
        }

        if ($request->has('metric')) {
            $query->where('metric_identifier', $request->query('metric'));
        }

        if ($request->has('metric_identifier')) {
            $query->where('metric_identifier', $request->query('metric_identifier'));
        }

        $limit = min((int) ($request->query('limit', 20)), 100);
        $events = $query->orderBy('timestamp', 'desc')->limit($limit)->get();

        return UsageEventResource::collection($events);
    }
}
