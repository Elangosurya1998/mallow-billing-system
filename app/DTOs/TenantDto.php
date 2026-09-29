<?php

declare(strict_types=1);

namespace App\DTOs;

final class TenantDto
{
    public private(set) ?string $id;

    public private(set) string $name;

    public private(set) string $slug;

    public private(set) string $email;

    public private(set) string $currency;

    public private(set) int $creditBalanceCents;

    public private(set) string $status;

    public private(set) string $timezone;

    public function __construct(
        string $name,
        string $slug,
        string $email,
        string $currency = 'USD',
        int $creditBalanceCents = 0,
        string $status = 'active',
        string $timezone = 'UTC',
        ?string $id = null,
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->email = $email;
        $this->currency = strtoupper($currency);
        $this->creditBalanceCents = $creditBalanceCents;
        $this->status = $status;
        $this->timezone = $timezone;
        $this->id = $id;
    }

    public string $formattedCreditBalance {
        get => sprintf('%s %.2f', $this->currency, $this->creditBalanceCents / 100);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            email: (string) $data['email'],
            currency: (string) ($data['currency'] ?? 'USD'),
            creditBalanceCents: (int) ($data['credit_balance_cents'] ?? 0),
            status: (string) ($data['status'] ?? 'active'),
            timezone: (string) ($data['timezone'] ?? 'UTC'),
            id: isset($data['id']) ? (string) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'currency' => $this->currency,
            'status' => $this->status,
            'timezone' => $this->timezone,
        ], fn ($value) => $value !== null);
    }
}
