<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTask extends Model
{
    public const STATUSES = ['new', 'in_progress', 'completed', 'cancelled'];

    protected $fillable = [
        'order_id',
        'motorcycle_id',
        'assigned_user_id',
        'quantity',
        'status',
        'comment',
        'completed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function staffNotes()
    {
        return $this->morphMany(StaffNote::class, 'noteable');
    }
}
