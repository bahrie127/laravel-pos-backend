<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    use HasFactory;

    public const SHIFT_PAGI = 'Pagi';
    public const SHIFT_SIANG = 'Siang';
    public const SHIFT_MALAM = 'Malam';

    public const SHIFTS = [self::SHIFT_PAGI, self::SHIFT_SIANG, self::SHIFT_MALAM];

    protected $fillable = [
        'user_id',
        'shift_label',
        'opening_float',
        'opening_note',
        'opened_at',
        'cash_in',
        'cash_out',
        'physical_count',
        'expected_cash',
        'variance',
        'closing_note',
        'closed_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_float' => 'integer',
        'cash_in' => 'integer',
        'cash_out' => 'integer',
        'physical_count' => 'integer',
        'expected_cash' => 'integer',
        'variance' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'cash_session_id');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->closed_at === null;
    }

    public function getIsBalancedAttribute(): bool
    {
        return $this->variance !== null && $this->variance === 0;
    }

    /**
     * Sum of `amount_paid` for paid cash orders attached to this session.
     * Used at close to compute `expected_cash`.
     */
    public function cashRevenue(): int
    {
        return (int) $this->orders()
            ->where('payment_method', 'Tunai')
            ->where('status', Order::STATUS_PAID)
            ->sum('amount_paid');
    }

    /**
     * Convenience aggregates used in the close-shift reconciliation card.
     */
    public function revenueByMethod(): array
    {
        return $this->orders()
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_price) as amount')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->payment_method ?? 'unknown' => [
                    'count' => (int) $row->count,
                    'amount' => (int) $row->amount,
                ],
            ])
            ->toArray();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Returns the active (unclosed) session for the given user, or null.
     */
    public static function currentFor(int $userId): ?self
    {
        return self::query()
            ->forUser($userId)
            ->open()
            ->latest('opened_at')
            ->first();
    }
}
