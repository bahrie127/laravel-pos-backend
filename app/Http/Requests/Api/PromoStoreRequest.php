<?php

namespace App\Http\Requests\Api;

use App\Models\Promo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(Promo::TYPES)],
            'value' => ['required', 'integer', 'min:0', 'max:100000000'],
            'code' => ['nullable', 'string', 'max:50', 'unique:promos,code'],
            'applies_to' => ['nullable', 'array'],
            'min_subtotal' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
