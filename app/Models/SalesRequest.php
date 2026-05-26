<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRequest extends Model
{
    public const STATUSES = ['new', 'in_progress', 'approved', 'completed', 'cancelled'];

    public const TYPES = ['purchase', 'consultation', 'availability', 'preorder', 'test_drive'];

    protected $fillable = [
        'user_id',
        'motorcycle_id',
        'name',
        'phone',
        'email',
        'type',
        'comment',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function motorcycle()
    {
        return $this->belongsTo(Motorcycle::class);
    }
}
