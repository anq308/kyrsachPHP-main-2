<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(string $action, ?Model $entity = null, ?User $user = null, ?string $description = null, ?array $before = null, ?array $after = null): void
    {
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
        ]);
    }
}
