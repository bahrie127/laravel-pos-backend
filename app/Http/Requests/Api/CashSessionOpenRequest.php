<?php

namespace App\Http\Requests\Api;

use App\Models\CashSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashSessionOpenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shift_label' => ['required', 'string', Rule::in(CashSession::SHIFTS)],
            'opening_float' => ['required', 'integer', 'min:0'],
            'opening_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
