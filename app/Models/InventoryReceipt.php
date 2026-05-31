<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceipt extends Model
{
    public const STATUSES = ['planned', 'received', 'cancelled'];

    protected $fillable = [
        'motorcycle_id',
        'user_id',
        'supplier_name',
        'quantity',
        'unit_cost',
        'status',
        'expected_at',
        'received_at',
        'comment',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'integer',
        'expected_at' => 'date',
        'received_at' => 'datetime',
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
