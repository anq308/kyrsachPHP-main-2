<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const STATUSES = ['new', 'processing', 'approved', 'ready_for_pickup', 'completed', 'cancelled'];

    public const PAYMENT_METHODS = ['cash_pickup', 'card_pickup', 'online_mock', 'credit_request'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'comment',
        'total',
        'status',
        'payment_method',
        'payment_status',
        'pickup_point_id',
        'pickup_ready_at',
    ];

    protected $casts = [
        'pickup_ready_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
