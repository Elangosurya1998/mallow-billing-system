<?php

declare(strict_types=1);

namespace App\DTOs;

final class PlanDto
{
    public private(set) ?string $id;

    public private(set) string $name;

    public private(set) string $slug;

    public private(set) ?string $description;

    public private(set) string $invoiceInterval;

    public private(set) int $basePriceCents;

    public private(set) int $trialPeriodDays;

    public private(set) bool $isActive;

    /**
     * @var array<int, PriceTierDto>
     */
    public private(set) array $priceTiers;

    /**
     * @param  array<int, PriceTierDto>  $priceTiers
     */
    public function __construct(
        string $name,
        string $slug,
        string $invoiceInterval = 'month',
        int $basePriceCents = 0,
        int $trialPeriodDays = 0,
        bool $isActive = true,
        ?string $description = null,
        array $priceTiers = [],
        ?string $id = null,
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->invoiceInterval = $invoiceInterval;
        $this->basePriceCents = $basePriceCents;
        $this->trialPeriodDays = $trialPeriodDays;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->priceTiers = $priceTiers;
        $this->id = $id;
    }

    public string $formattedBasePrice {
        get => sprintf('$%.2f', $this->basePriceCents / 100);
    }

    public static function fromArray(array $data): self
    {
        $tiers = [];
        if (isset($data['price_tiers']) && is_array($data['price_tiers'])) {
            foreach ($data['price_tiers'] as $tier) {
                $tiers[] = $tier instanceof PriceTierDto ? $tier : PriceTierDto::fromArray($tier);
            }
        }

        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            invoiceInterval: (string) ($data['invoice_interval'] ?? 'month'),
            basePriceCents: (int) ($data['base_price_cents'] ?? 0),
            trialPeriodDays: (int) ($data['trial_period_days'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            description: isset($data['description']) ? (string) $data['description'] : null,
            priceTiers: $tiers,
            id: isset($data['id']) ? (string) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'invoice_interval' => $this->invoiceInterval,
            'base_price_cents' => $this->basePriceCents,
            'trial_period_days' => $this->trialPeriodDays,
            'is_active' => $this->isActive,
            'price_tiers' => array_map(fn (PriceTierDto $tier) => $tier->toArray(), $this->priceTiers),
        ], fn ($value) => $value !== null);
    }
}
