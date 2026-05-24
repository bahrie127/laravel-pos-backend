<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'transaction_time' => optional($this->transaction_time)->toIso8601String(),
            'total_price' => (int) $this->total_price,
            'total_item' => (int) $this->total_item,
            'kasir_id' => $this->kasir_id,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'subtotal' => (int) $this->subtotal,
            'discount' => (int) $this->discount,
            'tax' => (int) $this->tax,
            'amount_paid' => (int) $this->amount_paid,
            'change_amount' => (int) $this->change_amount,
            'customer_name' => $this->customer_name,
            'notes' => $this->notes,
            'kasir' => new UserResource($this->whenLoaded('kasir')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
