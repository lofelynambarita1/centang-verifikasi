<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AuditSystemNotification;
use Illuminate\Database\Eloquent\Collection;

class NotificationDispatcher
{
    public function broadcast(AuditSystemNotification $notification): void
    {
        User::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $users) use ($notification): void {
                foreach ($users as $user) {
                    $user->notify($notification);
                }
            });
    }
}
