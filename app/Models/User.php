<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CLIENT = 'client';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_CLIENT,
        self::ROLE_MANAGER,
        self::ROLE_ADMIN,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('role')) {
                $user->is_admin = $user->role === self::ROLE_ADMIN;

                return;
            }

            if ($user->is_admin && empty($user->role)) {
                $user->role = self::ROLE_ADMIN;
            }

            if (empty($user->role)) {
                $user->role = self::ROLE_CLIENT;
            }
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || (bool) $this->is_admin;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function canManagePanel(): bool
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function salesRequests()
    {
        return $this->hasMany(SalesRequest::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function clientNotifications()
    {
        return $this->hasMany(ClientNotification::class);
    }

    public function hasFavorite($motorcycleId): bool
    {
        return $this->favorites()->where('motorcycle_id', $motorcycleId)->exists();
    }
}
