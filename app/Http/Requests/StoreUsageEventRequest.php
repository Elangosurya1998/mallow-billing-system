<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsageEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metric_identifier' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:128'],
            'timestamp' => ['sometimes', 'date'],
            'properties' => ['sometimes', 'array'],
        ];
    }
}
