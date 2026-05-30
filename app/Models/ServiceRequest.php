<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    public const STATUSES = ['new', 'confirmed', 'in_service', 'done', 'cancelled'];

    protected $fillable = [
        'user_id',
        'service_slot_id',
        'name',
        'phone',
        'email',
        'motorcycle_model',
        'service_type',
        'preferred_date',
        'comment',
        'status',
    ];

    protected $casts = [
        'preferred_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceSlot()
    {
        return $this->belongsTo(ServiceSlot::class);
    }
}
