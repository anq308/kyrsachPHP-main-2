<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'method',
        'status',
        'transaction_id',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
