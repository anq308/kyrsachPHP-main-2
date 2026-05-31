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
        'stock_quantity',
        'reserved_quantity',
        'transmission', // New
        'cooling',      // New
        'fuel_system',  // New
        'weight',       // New
        'tank_capacity', // New
        'views_count',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function getImageUrlAttribute(?string $value): ?string
    {
        if (! $value || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return rtrim(config('app.url'), '/').$value;
        }

        return $value;
    }

    public function availableStock(): int
    {
        return max(0, (int) $this->stock_quantity - (int) $this->reserved_quantity);
    }

    public function canReserve(int $quantity = 1): bool
    {
        return $this->is_available && $this->availableStock() >= $quantity;
    }

    public function refreshAvailability(): void
    {
        $this->forceFill([
            'is_available' => $this->availableStock() > 0,
        ])->save();
    }

    public function salesRequests()
    {
        return $this->hasMany(SalesRequest::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function inventoryReceipts()
    {
        return $this->hasMany(InventoryReceipt::class);
    }

    public function staffNotes()
    {
        return $this->morphMany(StaffNote::class, 'noteable');
    }
}
