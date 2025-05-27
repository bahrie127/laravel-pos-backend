<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

      protected $fillable = [
        'cashier_id',
        'open_amount',
        'close_amount',
        'cash_sales',
        'status',
        'opened_at',
        'closed_at',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function getDifferenceAttribute()
    {
        if (is_null($this->close_amount) || is_null($this->cash_sales)) {
            return null;
        }

        return ($this->open_amount + $this->cash_sales) - $this->close_amount;
    }
}
