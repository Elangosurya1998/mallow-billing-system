<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next, bool $required = true): Response
    {
        $tenantId = $request->header('X-Tenant-ID')
            ?? $request->route('tenant')
            ?? $request->input('tenant_id');

        if ($tenantId instanceof Tenant) {
            $this->tenantContext->setTenant($tenantId);

            return $next($request);
        }

        if (is_string($tenantId) && $tenantId !== '') {
            $tenant = Tenant::query()
                ->where('id', $tenantId)
                ->orWhere('slug', $tenantId)
                ->first();

            if ($tenant) {
                $this->tenantContext->setTenant($tenant);

                return $next($request);
            }

            if ($required) {
                return response()->json([
                    'message' => 'Tenant not found.',
                ], Response::HTTP_NOT_FOUND);
            }
        } elseif ($required) {
            return response()->json([
                'message' => 'Tenant identifier (X-Tenant-ID header) is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
