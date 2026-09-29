<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string', 'exists:plans,id'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
