<?php

namespace App\Services;

use App\Models\ClientNotification;
use App\Models\User;

class ClientNotificationService
{
    public function create(?User $user, string $title, string $message, string $type = 'order'): void
    {
        if (! $user) {
            return;
        }

        ClientNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }
}
