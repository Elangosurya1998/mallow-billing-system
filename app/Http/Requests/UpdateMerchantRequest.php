<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $merchant = $this->route('merchant') ?? $this->route('tenant');
        $merchantId = is_object($merchant) ? $merchant->id : $merchant;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('merchants', 'slug')->ignore($merchantId),
            ],
            'email' => ['required', 'email', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'timezone'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
