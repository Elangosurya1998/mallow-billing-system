<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Plans\CreatePlanAction;
use App\DTOs\PlanDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $plans = Plan::with('priceTiers')
            ->where('is_active', true)
            ->latest()
            ->get();

        return PlanResource::collection($plans);
    }

    public function store(StorePlanRequest $request, CreatePlanAction $action): JsonResponse
    {
        $dto = PlanDto::fromArray($request->validated());
        $plan = $action->execute($dto);

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Plan $plan): PlanResource
    {
        return new PlanResource($plan->load('priceTiers'));
    }
}
