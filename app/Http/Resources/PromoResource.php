<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'value' => (int) $this->value,
            'code' => $this->code,
            'applies_to' => $this->applies_to,
            'min_subtotal' => (int) $this->min_subtotal,
            'starts_at' => optional($this->starts_at)->toIso8601String(),
            'ends_at' => optional($this->ends_at)->toIso8601String(),
            'active' => (bool) $this->active,
            'is_live' => $this->isLive(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
