<?php

declare(strict_types=1);

namespace App\Actions\Plans;

use App\DTOs\PlanDto;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class CreatePlanAction
{
    public function execute(PlanDto $dto): Plan
    {
        return DB::transaction(function () use ($dto): Plan {
            $plan = Plan::create([
                'name' => $dto->name,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'invoice_interval' => $dto->invoiceInterval,
                'base_price_cents' => $dto->basePriceCents,
                'trial_period_days' => $dto->trialPeriodDays,
                'is_active' => $dto->isActive,
            ]);

            foreach ($dto->priceTiers as $tier) {
                $plan->priceTiers()->create($tier->toArray());
            }

            return $plan->load('priceTiers');
        });
    }
}
