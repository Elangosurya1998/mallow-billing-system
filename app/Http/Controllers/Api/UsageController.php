<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Ingestion\RecordUsageAction;
use App\DTOs\RecordUsageDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordUsageRequest;
use App\Http\Resources\UsageEventResource;
use Illuminate\Http\JsonResponse;

class UsageController extends Controller
{
    /**
     * Ingest a usage event idempotently.
     */
    public function store(RecordUsageRequest $request, RecordUsageAction $action): JsonResponse
    {
        $dto = RecordUsageDto::fromArray($request->validated());
        $result = $action->execute($dto);

        return (new UsageEventResource($result->event))
            ->additional([
                'idempotent_replay' => $result->isDuplicate,
            ])
            ->response()
            ->setStatusCode($result->httpStatusCode);
    }
}
