<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSlot extends Model
{
    public const STATUSES = ['available', 'booked', 'closed'];

    protected $fillable = [
        'service_date',
        'starts_at',
        'ends_at',
        'service_type',
        'capacity',
        'booked_count',
        'status',
        'comment',
    ];

    protected $casts = [
        'service_date' => 'date',
        'capacity' => 'integer',
        'booked_count' => 'integer',
    ];

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function hasFreeCapacity(): bool
    {
        return $this->status === 'available' && $this->booked_count < $this->capacity;
    }
}
