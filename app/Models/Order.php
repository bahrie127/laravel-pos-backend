<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'transaction_time',
        'total_price',
        'total_item',
        'kasir_id',
        'cash_session_id',
        'payment_method',
        'status',
        'subtotal',
        'discount',
        'tax',
        'amount_paid',
        'change_amount',
        'customer_name',
        'notes',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'total_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (! $order->status) {
                $order->status = self::STATUS_PAID;
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $date = now();
        $countToday = self::whereDate('created_at', $date->toDateString())->count() + 1;

        return 'INV-' . $date->format('Ymd') . '-' . str_pad((string) $countToday, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_REFUNDED => 'Refund',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-soft-warning',
            self::STATUS_PAID => 'badge-soft-success',
            self::STATUS_CANCELLED => 'badge-soft-danger',
            self::STATUS_REFUNDED => 'badge-soft-secondary',
            default => 'badge-soft-secondary',
        };
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id', 'id');
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
