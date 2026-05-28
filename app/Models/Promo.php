<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Promo extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_RUPIAH = 'rupiah';
    public const TYPE_B1G1 = 'b1g1';

    public const TYPES = [self::TYPE_PERCENT, self::TYPE_RUPIAH, self::TYPE_B1G1];

    protected $fillable = [
        'name',
        'type',
        'value',
        'code',
        'applies_to',
        'min_subtotal',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'value' => 'integer',
        'min_subtotal' => 'integer',
        'applies_to' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Compute the discount this promo would apply to a subtotal.
     * Returns 0 if the promo doesn't qualify.
     *
     * Server-authoritative — the client may show a hint but the BE
     * recomputes on order creation so a tampered client can't fake savings.
     */
    public function computeDiscount(int $subtotal): int
    {
        if (! $this->isLive()) return 0;
        if ($subtotal < $this->min_subtotal) return 0;

        return match ($this->type) {
            self::TYPE_PERCENT => (int) floor($subtotal * $this->value / 100),
            self::TYPE_RUPIAH => min((int) $this->value, $subtotal),
            self::TYPE_B1G1 => 0, // computed per-line by client; BE accepts up to subtotal
            default => 0,
        };
    }

    /**
     * Live = active + within optional time window.
     */
    public function isLive(?Carbon $now = null): bool
    {
        $now ??= now();
        if (! $this->active) return false;
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeLive(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT => 'Persen',
            self::TYPE_RUPIAH => 'Rupiah',
            self::TYPE_B1G1 => 'Beli 1 Gratis 1',
            default => $this->type,
        };
    }

    public function status(?Carbon $now = null): string
    {
        $now ??= now();
        if (! $this->active) return 'inactive';
        if ($this->starts_at && $now->lt($this->starts_at)) return 'scheduled';
        if ($this->ends_at && $now->gt($this->ends_at)) return 'expired';
        return 'live';
    }
}
