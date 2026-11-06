<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('update-audit-summary', fn (User $user, Audit $audit): bool => $user->isAdmin()
            || $audit->userIsLead($user));

        View::composer('layouts.app', function (ViewContract $view): void {
            $user = auth()->user();
            $today = today();
            $deadlineReminders = $user
                ? Audit::query()
                    ->with('company:id,name')
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<=', $today->copy()->addDays(7)->toDateString())
                    ->orderBy('due_date')
                    ->limit(3)
                    ->get()
                    ->map(function (Audit $audit) use ($today): array {
                        $daysRemaining = (int) $today->diffInDays($audit->due_date, false);
                        $isOverdue = $daysRemaining < 0;
                        $deadlineStatus = match (true) {
                            $isOverdue => 'Terlambat '.abs($daysRemaining).' hari',
                            $daysRemaining === 0 => 'Deadline hari ini',
                            default => 'Deadline '.$daysRemaining.' hari lagi',
                        };

                        return [
                            'key' => implode('-', ['ispo-deadline', $audit->id, $audit->due_date->toDateString(), $isOverdue ? 'overdue' : 'upcoming']),
                            'title' => $audit->title,
                            'company' => $audit->company?->name ?? 'Perusahaan',
                            'due_date' => $audit->due_date->locale('id')->translatedFormat('d F Y'),
                            'status' => $deadlineStatus,
                            'overdue' => $isOverdue,
                            'url' => route('audits.show', $audit, false),
                        ];
                    })
                : collect();

            $view->with([
                'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user?->notifications()->latest()->limit(5)->get() ?? collect(),
                'deadlineReminders' => $deadlineReminders,
            ]);
        });
    }
}
