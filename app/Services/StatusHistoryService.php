<?php

namespace App\Services;

use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StatusHistoryService
{
    public function record(Model $entity, ?string $oldStatus, string $newStatus, ?User $user = null, ?string $comment = null): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        StatusHistory::create([
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user?->id,
            'comment' => $comment,
        ]);
    }
}
