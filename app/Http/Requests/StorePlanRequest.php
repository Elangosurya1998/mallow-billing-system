<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'unique:plans,slug'],
            'description' => ['nullable', 'string'],
            'invoice_interval' => ['sometimes', 'string', 'in:month,year'],
            'base_price_cents' => ['required', 'integer', 'min:0'],
            'trial_period_days' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'price_tiers' => ['sometimes', 'array'],
            'price_tiers.*.metric_identifier' => ['required_with:price_tiers', 'string', 'max:64'],
            'price_tiers.*.tier_mode' => ['sometimes', 'string', 'in:graduated,volume,flat'],
            'price_tiers.*.first_unit' => ['required_with:price_tiers', 'integer', 'min:0'],
            'price_tiers.*.last_unit' => ['nullable', 'integer', 'min:1'],
            'price_tiers.*.unit_price_cents' => ['sometimes', 'integer', 'min:0'],
            'price_tiers.*.flat_fee_cents' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
