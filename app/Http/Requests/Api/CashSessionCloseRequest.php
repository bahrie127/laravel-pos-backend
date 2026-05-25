<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CashSessionCloseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'physical_count' => ['required', 'integer', 'min:0'],
            'cash_in' => ['nullable', 'integer', 'min:0'],
            'cash_out' => ['nullable', 'integer', 'min:0'],
            'closing_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
