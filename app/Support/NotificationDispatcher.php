<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AuditSystemNotification;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class NotificationDispatcher
{
    /** @param iterable<User> $users */
    public function send(iterable $users, AuditSystemNotification $notification): void
    {
        foreach ($users as $user) {
            try {
                $user->notify($notification);
            } catch (TransportExceptionInterface $exception) {
                report($exception);
            }
        }
    }

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
