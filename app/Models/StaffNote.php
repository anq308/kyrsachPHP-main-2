<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffNote extends Model
{
    public const ENTITY_TYPES = [
        'order' => Order::class,
        'sales_request' => SalesRequest::class,
        'service_request' => ServiceRequest::class,
        'warehouse_task' => WarehouseTask::class,
        'motorcycle' => Motorcycle::class,
        'customer' => User::class,
    ];

    protected $fillable = [
        'user_id',
        'noteable_type',
        'noteable_id',
        'body',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function noteable()
    {
        return $this->morphTo();
    }

    public static function resolveEntityClass(string $type): ?string
    {
        return self::ENTITY_TYPES[$type] ?? null;
    }
}
