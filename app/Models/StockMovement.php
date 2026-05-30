<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPES = ['receipt', 'write_off', 'reservation', 'release', 'correction'];

    protected $fillable = [
        'motorcycle_id',
        'user_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reserved_before',
        'reserved_after',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'reserved_before' => 'integer',
        'reserved_after' => 'integer',
    ];

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
