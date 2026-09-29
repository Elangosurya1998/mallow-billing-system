<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?string
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    /**
     * @template TReturn
     *
     * @param  callable(Tenant): TReturn  $callback
     * @return TReturn
     */
    public function executeInTenantContext(Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->tenant;
        $this->setTenant($tenant);

        try {
            return $callback($tenant);
        } finally {
            $this->setTenant($previous);
        }
    }
}
