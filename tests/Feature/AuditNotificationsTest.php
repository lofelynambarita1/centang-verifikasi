<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Notifications\AuditSystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuditNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_read_and_mark_owned_notifications_as_read(): void
    {
        $user = User::factory()->create(['role' => 'auditor']);
        $otherUser = User::factory()->create(['role' => 'auditor']);
        $user->notify(new AuditSystemNotification(
            'audit_result',
            'Hasil audit baru',
            'Hasil baru tersedia untuk audit PT Contoh.',
            route('dashboard', [], false),
        ));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Total notifikasi')
            ->assertSee('Belum dibaca')
            ->assertSee('Hasil audit baru')
            ->assertSee('Hasil baru tersedia untuk audit PT Contoh.')
            ->assertSee('notification-item__icon', false)
            ->assertSee('app-nav__badge', false);

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_account_request_notification_is_distinct_and_links_to_account_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new AuditSystemNotification(
            'role_request',
            'Permintaan akun baru',
            'Pemohon Baru meminta pendaftaran akun dan menunggu persetujuan Master Admin.',
            route('users.index', [], false),
        ));

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('notification-item--account-request', false)
            ->assertSee('Permintaan akun baru')
            ->assertSee('Pemohon Baru meminta pendaftaran akun')
            ->assertSee('Tinjau akun')
            ->assertSee(route('notifications.read', $admin->notifications()->firstOrFail()->id), false);
    }

    public function test_role_request_notifies_active_admins_via_database_and_mail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'lead_audit']);
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Pemohon Role',
            'email' => 'pemohon@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'lead_audit',
        ])->assertRedirect(route('login'));

        Notification::assertSentTo($admin, AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'role_request'
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true);
        });
        Notification::assertNotSentTo($lead, AuditSystemNotification::class);
    }

    public function test_new_principle_assignments_notify_only_new_assignees(): void
    {
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit penugasan']);
        Notification::fake();

        $this->actingAs($lead)->put(route('audits.assignments.update', $audit), [
            'assignments' => [
                ['principle' => 1, 'users' => [$auditor->id]],
                ['principle' => 2, 'users' => [$auditor->id]],
            ],
        ])->assertRedirect();

        Notification::assertSentTo($auditor, AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'principle_assignment'
                && str_contains($notification->message, 'Prinsip 1, 2')
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true);
        });
        Notification::assertNotSentTo($lead, AuditSystemNotification::class);
    }

    public function test_new_results_and_findings_notify_active_managers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit hasil']);
        $item = $audit->items()->create(['title' => '1.1.1 Indikator pertama', 'sort_order' => 1]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);
        Notification::fake();

        $this->actingAs($auditor)->post(route('audit-items.hasil.store', $item), [
            'content' => 'Dokumen pendukung sudah diperiksa.',
        ])->assertRedirect();

        Notification::assertSentTo([$admin, $lead], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_result';
        });
        Notification::assertNotSentTo($auditor, AuditSystemNotification::class);

        $this->put(route('audit-items.update', $item), ['status' => 'non_compliant'])
            ->assertRedirect();

        Notification::assertSentTo([$admin, $lead], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_finding';
        });
    }

    public function test_audit_completion_notifies_assignees_and_active_managers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit selesai']);
        $item = $audit->items()->create([
            'title' => '1.1.1 Indikator pertama',
            'sort_order' => 1,
            'status' => 'compliant',
        ]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);
        Notification::fake();

        $this->actingAs($admin)->post(route('audits.complete', $audit))->assertRedirect();

        Notification::assertSentTo([$lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_completed';
        });
        Notification::assertNotSentTo($admin, AuditSystemNotification::class);

    }
}
