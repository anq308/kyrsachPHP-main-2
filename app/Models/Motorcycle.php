<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motorcycle extends Model
{
    /** @use HasFactory<\Database\Factories\MotorcycleFactory> */
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'type',
        'year',
        'engine_capacity',
        'power',
        'price',
        'description',
        'image_url',
        'is_available',
        'transmission', // New
        'cooling',      // New
        'fuel_system',  // New
        'weight',       // New
        'tank_capacity', // New
        'views_count',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function salesRequests()
    {
        return $this->hasMany(SalesRequest::class);
    }
}
