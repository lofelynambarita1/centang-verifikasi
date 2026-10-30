<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Notifications\AuditSystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Hasil audit baru')
            ->assertSee('app-nav__badge', false);

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_role_approval_notifies_every_user_in_the_system_in_app(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'auditor', 'requested_role' => 'lead_audit']);
        $otherUser = User::factory()->create(['role' => 'auditor']);
        Notification::fake();

        $this->actingAs($admin)->post(route('users.approve-role', $user))
            ->assertRedirect(route('users.index'));

        Notification::assertSentTo([$admin, $user, $otherUser], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'role_approved'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });
    }

    public function test_principle_assignment_changes_notify_every_user_in_the_system(): void
    {
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $admin = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create(['name' => 'PT Contoh']);
        $audit = $company->audits()->create(['title' => 'Audit penugasan']);
        Notification::fake();

        $this->actingAs($lead)->put(route('audits.assignments.update', $audit), [
            'lead_auditor_id' => $lead->id,
            'team_members_present' => '1',
            'team_members' => [$auditor->id],
            'assignments' => [
                ['principle' => 1, 'user_id' => $auditor->id],
                ['principle' => 2, 'user_id' => $auditor->id],
            ],
        ])->assertRedirect();

        Notification::assertCount(3);
        Notification::assertSentTo([$lead, $auditor, $admin], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'principle_assignment'
                && str_contains($notification->message, 'PT Contoh')
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });
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

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'audit_result'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });

        $this->put(route('audit-items.update', $item), ['status' => 'non_compliant'])
            ->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'audit_finding'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });
    }

    public function test_audit_completion_notifies_every_user_in_the_system(): void
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

        Notification::assertSentTo([$lead, $auditor, $admin], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'audit_completed'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });
    }

    public function test_company_create_update_and_delete_broadcast_each_activity_to_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        Notification::fake();

        $this->actingAs($admin)->post(route('companies.store'), [
            'name' => 'PT Notifikasi Global',
        ])->assertRedirect(route('companies.index'));
        $company = Company::where('name', 'PT Notifikasi Global')->firstOrFail();

        Notification::assertSentTo([$admin, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'company_created'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });

        $newUser = User::factory()->create(['role' => 'auditor']);
        $this->put(route('companies.update', $company), ['name' => 'PT Notifikasi Diperbarui'])
            ->assertRedirect(route('companies.index'));

        Notification::assertSentTo([$admin, $auditor, $newUser], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'company_updated';
        });

        $this->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        Notification::assertSentTo([$admin, $auditor, $newUser], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'company_deleted';
        });
    }

    public function test_audit_creation_broadcasts_activity_to_every_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'auditor']);
        $otherUser = User::factory()->create(['role' => 'auditor']);
        $company = Company::factory()->create(['name' => 'PT Audit Global']);
        Notification::fake();

        $this->actingAs($admin)->post(route('audits.store'), [
            'company_id' => $company->id,
            'title' => 'Audit notifikasi',
            'start_date' => '2026-10-30',
            'end_date' => '2026-11-06',
            'lead_auditor_id' => $lead->id,
        ])->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $otherUser], AuditSystemNotification::class, function (AuditSystemNotification $notification, array $channels): bool {
            return $notification->event === 'audit_created'
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true);
        });
    }

    public function test_result_edits_and_deletions_notify_every_user(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit hasil global']);
        $item = $audit->items()->create(['title' => '1.1.1 Dokumen legal', 'sort_order' => 1]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);
        $hasil = $item->hasils()->create(['content' => 'Isi awal', 'sort_order' => 0]);
        Notification::fake();

        $this->actingAs($auditor)->post(route('audit-items.evidence', $item), [
            'evidence' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_evidence_uploaded';
        });

        $this->actingAs($auditor)->put(route('audit-hasils.update', $hasil), [
            'content' => 'Hasil sudah diperiksa.',
        ])->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_result_updated';
        });

        $this->delete(route('audit-hasils.destroy', $hasil))->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'audit_result_deleted';
        });
    }

    public function test_checklist_review_updates_notify_every_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lead = User::factory()->create(['role' => 'lead_audit']);
        $auditor = User::factory()->create(['role' => 'auditor']);
        $audit = Company::factory()->create()->audits()->create(['title' => 'Audit review global']);
        $item = $audit->items()->create(['title' => '1.1.1 Legalitas', 'sort_order' => 1]);
        $audit->assignees()->attach($auditor, ['principle_number' => 1]);
        Notification::fake();

        $this->actingAs($auditor)->put(route('audit-items.update', $item), [
            'status' => 'compliant',
            'note' => 'Dokumen sudah lengkap.',
        ])->assertRedirect();

        Notification::assertSentTo([$admin, $lead, $auditor], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'checklist_review_updated';
        });
    }

    public function test_user_creation_update_and_deletion_notify_all_existing_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $watcher = User::factory()->create(['role' => 'auditor']);
        Notification::fake();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Auditor Baru',
            'email' => 'auditor.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'auditor',
        ])->assertRedirect(route('users.index'));
        $newUser = User::where('email', 'auditor.baru@example.com')->firstOrFail();

        Notification::assertSentTo([$admin, $watcher, $newUser], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'user_created';
        });

        $this->put(route('users.update', $newUser), [
            'name' => 'Auditor Diperbarui',
            'email' => 'auditor.baru@example.com',
            'role' => 'auditor',
        ])->assertRedirect(route('users.index'));

        Notification::assertSentTo([$admin, $watcher, $newUser], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'user_updated';
        });

        $this->delete(route('users.destroy', $newUser))
            ->assertRedirect(route('users.index'));

        Notification::assertSentTo([$admin, $watcher], AuditSystemNotification::class, function (AuditSystemNotification $notification): bool {
            return $notification->event === 'user_deleted';
        });
    }

    public function test_notification_page_explains_system_activity_and_empty_state(): void
    {
        $user = User::factory()->create(['role' => 'auditor']);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Semua kabar penting, dalam satu tempat')
            ->assertSee('Total aktivitas')
            ->assertSee('Belum ada aktivitas')
            ->assertSee('review checklist');
    }
}
