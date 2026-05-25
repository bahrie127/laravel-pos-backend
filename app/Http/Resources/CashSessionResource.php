<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'shift_label' => $this->shift_label,
            'opening_float' => (int) $this->opening_float,
            'opening_note' => $this->opening_note,
            'opened_at' => optional($this->opened_at)->toIso8601String(),
            'cash_in' => (int) $this->cash_in,
            'cash_out' => (int) $this->cash_out,
            'physical_count' => $this->physical_count !== null
                ? (int) $this->physical_count
                : null,
            'expected_cash' => $this->expected_cash !== null
                ? (int) $this->expected_cash
                : null,
            'variance' => $this->variance !== null ? (int) $this->variance : null,
            'closing_note' => $this->closing_note,
            'closed_at' => optional($this->closed_at)->toIso8601String(),
            'is_open' => $this->is_open,
            'is_balanced' => $this->is_balanced,
        ];
    }
}
