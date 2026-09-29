<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\DTOs\TenantDto;
use App\Models\Tenant;

class CreateTenantAction
{
    public function execute(TenantDto $dto): Tenant
    {
        return Tenant::create($dto->toArray());
    }
}
