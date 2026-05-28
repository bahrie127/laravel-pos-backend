<?php

namespace App\Http\Requests;

use App\Models\Promo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $promo = $this->route('promo');

        return $promo
            ? ($this->user()?->can('update', $promo) ?? false)
            : ($this->user()?->can('create', Promo::class) ?? false);
    }

    public function rules(): array
    {
        $promoId = $this->route('promo')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(Promo::TYPES)],
            'value' => ['required', 'integer', 'min:0', 'max:100000000'],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('promos', 'code')->ignore($promoId),
            ],
            'min_subtotal' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode voucher sudah dipakai promo lain.',
            'ends_at.after_or_equal' => 'Tanggal berakhir harus setelah tanggal mulai.',
            'value.max' => 'Nilai maksimum 100.000.000.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->input('type') === Promo::TYPE_PERCENT) {
                $val = (int) $this->input('value');
                if ($val < 1 || $val > 100) {
                    $v->errors()->add('value', 'Untuk tipe Persen, nilai harus 1–100.');
                }
            }
        });
    }
}
