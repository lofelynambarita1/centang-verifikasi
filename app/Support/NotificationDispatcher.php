<?php

namespace App\Support;

use App\Notifications\AuditSystemNotification;
use App\Models\User;
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
}
