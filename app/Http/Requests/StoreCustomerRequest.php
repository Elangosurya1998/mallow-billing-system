<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'credit_balance' => ['nullable', 'numeric', 'min:0'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
        ];
    }
}
