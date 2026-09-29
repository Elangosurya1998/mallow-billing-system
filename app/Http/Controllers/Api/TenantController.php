<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Tenants\CreateTenantAction;
use App\DTOs\TenantDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateMerchantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $dto = TenantDto::fromArray($request->validated());
        $tenant = $action->execute($dto);

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant);
    }

    public function update(UpdateMerchantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant->update([
            'name' => (string) $request->validated('name'),
            'slug' => (string) $request->validated('slug'),
            'email' => (string) $request->validated('email'),
            'currency' => strtoupper((string) $request->validated('currency')),
            'timezone' => (string) $request->validated('timezone'),
            'status' => (string) $request->validated('status'),
        ]);

        return new TenantResource($tenant);
    }
}
