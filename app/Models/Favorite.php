<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'motorcycle_id',
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
