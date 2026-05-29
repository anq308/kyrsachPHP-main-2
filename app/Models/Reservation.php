<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const STATUSES = ['active', 'released', 'expired', 'completed'];

    protected $fillable = [
        'user_id',
        'order_id',
        'motorcycle_id',
        'quantity',
        'status',
        'expires_at',
        'released_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }
}
