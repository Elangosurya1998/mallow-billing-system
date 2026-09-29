<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchUsageEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:5000'],
            'events.*.metric_identifier' => ['required', 'string', 'max:64'],
            'events.*.quantity' => ['required', 'integer', 'min:1'],
            'events.*.idempotency_key' => ['required', 'string', 'max:128'],
            'events.*.timestamp' => ['sometimes', 'date'],
            'events.*.properties' => ['sometimes', 'array'],
        ];
    }
}
