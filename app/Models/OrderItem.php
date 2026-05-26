<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'motorcycle_id',
        'name',
        'price',
        'quantity',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }
}
