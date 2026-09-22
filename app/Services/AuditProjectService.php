<?php

namespace App\Services;

use App\Enums\AuditProjectStatus;
use App\Enums\ProjectRole;
use App\Models\AuditIndicatorAssignment;
use App\Models\AuditProject;
use App\Models\AuditProjectMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuditProjectService
{
    private const MAX_PROJECT_CODE_ATTEMPTS = 5;

    public function __construct(private readonly TemplateSnapshotService $snapshots) {}

    /**
     * Create a project together with its team members, a snapshot copy of the
     * chosen template structure and an activity log inside a single database
     * transaction. The generated project code is stable (does not depend on the
     * auto-increment id) and unique.
     *
     * @param  array{template_id: int, company_id: int, name: string, audit_type: string, audit_start_date: string, audit_end_date: string, land_area?: string|float|null, lead_auditor_id: int, auditor_ids?: array<int>}  $data
     */
    public function createProject(array $data, User $actor): AuditProject
    {
        return DB::transaction(function () use ($data, $actor): AuditProject {
            $project = $this->createWithUniqueProjectCode($data, $actor);

            $this->addMember($project, User::findOrFail($data['lead_auditor_id']), ProjectRole::LeadAuditor, $actor);

            foreach ($data['auditor_ids'] ?? [] as $auditorId) {
                $this->addMember($project, User::findOrFail($auditorId), ProjectRole::Auditor, $actor);
            }

            $this->snapshot($project, $actor);
            $this->log($project, $actor, 'project.created', 'Project audit "'.$project->name.'" dibuat.');

            return $project;
        });
    }

    /**
     * @param  array{template_id: int, company_id: int, name: string, audit_type: string, audit_start_date: string, audit_end_date: string, land_area?: string|float|null, status: string}  $data
     */
    public function updateProject(AuditProject $project, array $data, User $actor): AuditProject
    {
        return DB::transaction(function () use ($project, $data, $actor): AuditProject {
            $templateChanged = (int) $data['template_id'] !== (int) $project->template_id;

            $this->assertValidStatusTransition($project, $data['status']);

            $nextStatus = AuditProjectStatus::from($data['status']);

            if ($templateChanged
                && ! in_array($project->status, [AuditProjectStatus::Draft, AuditProjectStatus::TeamSetup], true)) {
                throw ValidationException::withMessages([
                    'template_id' => 'Template hanya dapat diganti saat status Draft atau Penyusunan Tim. Setelah penugasan berjalan, snapshot dan assignment tidak boleh rusak.',
                ]);
            }

            if ($nextStatus === AuditProjectStatus::InProgress) {
                $unassigned = $project->unassignedIndicatorCount();

                if ($unassigned > 0) {
                    throw ValidationException::withMessages([
                        'status' => sprintf('Project tidak dapat masuk status "%s" karena masih ada %d indikator tanpa PIC.', $nextStatus->label(), $unassigned),
                    ]);
                }
            }

            $project->update([
                'template_id' => $data['template_id'],
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'audit_type' => $data['audit_type'],
                'audit_start_date' => $data['audit_start_date'],
                'audit_end_date' => $data['audit_end_date'],
                'land_area' => $data['land_area'] ?? null,
                'status' => $data['status'],
            ]);

            if ($templateChanged) {
                $this->replaceSnapshot($project, $actor);
            }

            $this->log($project, $actor, 'project.updated', 'Data project "'.$project->name.'" diperbarui.');

            return $project;
        });
    }

    /**
     * Copy the template structure into the project snapshot and log the event.
     */
    private function snapshot(AuditProject $project, User $actor): void
    {
        $template = $project->template;

        if ($template === null) {
            return;
        }

        $counts = $this->snapshots->snapshot($project, $template);

        $this->log($project, $actor, 'template.snapshot_created', sprintf(
            'Struktur template "%s" (versi %s) disalin ke project: %d prinsip, %d indikator, %d parameter.',
            $template->name,
            $template->version ?? '-',
            $counts['principles'],
            $counts['indicators'],
            $counts['parameters'],
        ), [
            'template_id' => $template->id,
            'template_code' => $template->code,
            'template_version' => $template->version,
            'principles_count' => $counts['principles'],
            'sections_count' => $counts['sections'],
            'indicators_count' => $counts['indicators'],
            'parameters_count' => $counts['parameters'],
        ]);
    }

    /**
     * Swap the project snapshot when its template is changed during an update.
     */
    private function replaceSnapshot(AuditProject $project, User $actor): void
    {
        $project->snapshotPrinciples()->delete();
        $this->snapshot($project, $actor);
    }

    public function addAuditor(AuditProject $project, User $member, User $actor): AuditProjectMember
    {
        return $this->addMember($project, $member, ProjectRole::Auditor, $actor);
    }

    public function removeMember(AuditProject $project, AuditProjectMember $member, User $actor): void
    {
        if ($member->isLead()) {
            throw ValidationException::withMessages([
                'member' => 'Lead Auditor tidak dapat dihapus dari project. Ganti Lead Auditor terlebih dahulu.',
            ]);
        }

        $assignmentCount = AuditIndicatorAssignment::query()
            ->where('audit_project_id', $project->id)
            ->where('pic_user_id', $member->user_id)
            ->count();

        if ($assignmentCount > 0) {
            throw ValidationException::withMessages([
                'member' => sprintf(
                    'Anggota "%s" masih menjadi PIC untuk %d indikator. Pindahkan assignment terlebih dahulu sebelum anggota dihapus.',
                    $member->user->name,
                    $assignmentCount,
                ),
            ]);
        }

        DB::transaction(function () use ($project, $member, $actor): void {
            $name = $member->user->name;
            $member->delete();

            $this->log($project, $actor, 'member.removed', 'Anggota "'.$name.'" dihapus dari project.');
        });
    }

    /**
     * Replace the active Lead Auditor atomically. The previous lead is demoted
     * to an ordinary auditor and stays in the project as a member.
     *
     * The project row and the affected membership rows are locked inside the
     * transaction so concurrent replacement requests serialize instead of
     * leaving the project with multiple leads.
     */
    public function replaceLeadAuditor(AuditProject $project, User $newLead, User $actor): AuditProjectMember
    {
        return DB::transaction(function () use ($project, $newLead, $actor): AuditProjectMember {
            $lockedProject = AuditProject::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldLead = $lockedProject->members()
                ->lockForUpdate()
                ->where('project_role', ProjectRole::LeadAuditor->value)
                ->first();

            if ($oldLead !== null && $oldLead->user_id === $newLead->id) {
                throw ValidationException::withMessages([
                    'new_lead_auditor_id' => 'User terpilih sudah menjadi Lead Auditor project ini.',
                ]);
            }

            if ($oldLead !== null) {
                $oldLead->update(['project_role' => ProjectRole::Auditor->value]);
            }

            $existing = $lockedProject->members()
                ->lockForUpdate()
                ->where('user_id', $newLead->id)
                ->first();

            $newLeadMember = $existing !== null
                ? tap($existing)->update(['project_role' => ProjectRole::LeadAuditor->value])
                : $lockedProject->members()->create([
                    'user_id' => $newLead->id,
                    'project_role' => ProjectRole::LeadAuditor->value,
                    'added_by' => $actor->id,
                ]);

            $this->log($lockedProject, $actor, 'lead_auditor.replaced', sprintf(
                'Lead Auditor diganti dari "%s" menjadi "%s".',
                $oldLead?->user->name ?? '-',
                $newLead->name,
            ), [
                'old_user_id' => $oldLead?->user_id,
                'new_user_id' => $newLead->id,
            ]);

            return $newLeadMember;
        });
    }

    /**
     * Insert the project while retrying whenever the generated code collides
     * with an existing unique value.
     *
     * @param  array{template_id: int, company_id: int, name: string, audit_type: string, audit_start_date: string, audit_end_date: string, land_area?: string|float|null}  $data
     */
    private function createWithUniqueProjectCode(array $data, User $actor): AuditProject
    {
        $fields = [
            'template_id' => $data['template_id'],
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'audit_type' => $data['audit_type'],
            'audit_start_date' => $data['audit_start_date'],
            'audit_end_date' => $data['audit_end_date'],
            'land_area' => $data['land_area'] ?? null,
            'status' => $data['status'] ?? AuditProjectStatus::Draft->value,
            'created_by' => $actor->id,
        ];

        for ($attempt = 0; $attempt < self::MAX_PROJECT_CODE_ATTEMPTS; $attempt++) {
            try {
                return AuditProject::create($fields + ['project_code' => $this->generateProjectCode()]);
            } catch (QueryException $e) {
                if (! $this->isUniqueViolation($e)) {
                    throw $e;
                }

                usleep(2000);
            }
        }

        throw new RuntimeException('Gagal membuat project code unik setelah beberapa percobaan.');
    }

    /**
     * Generate a human-readable, stable project code indepent of the eventual
     * primary key, e.g. PRJ-20260921-A4XK. Registration order is preserved and
     * collisions are resolved by the caller retrying the insert.
     */
    private function generateProjectCode(): string
    {
        return 'PRJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }

    /**
     * @param  array{0: string, 1?: int, 2?: string}|array{int, string, string}  $errorInfo
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        $message = $e->errorInfo[2] ?? '';

        return is_int($e->errorInfo[1] ?? null)
            && in_array($e->errorInfo[1], [19, 1062, 1555, 1567, 23505], true)
            || str_contains($message, 'UNIQUE constraint failed');
    }

    /**
     * Enforce the forward-only project workflow. Moving a project backwards
     * (e.g. from assignment to team_setup) would let an admin swap the template
     * and destroy the snapshot the assignments depend on.
     */
    private function assertValidStatusTransition(AuditProject $project, string $newStatusValue): void
    {
        $current = $project->status;
        $next = AuditProjectStatus::from($newStatusValue);

        if ($current === $next) {
            return;
        }

        $allowed = [
            AuditProjectStatus::Draft->value => [AuditProjectStatus::TeamSetup->value],
            AuditProjectStatus::TeamSetup->value => [AuditProjectStatus::Assignment->value],
            AuditProjectStatus::Assignment->value => [AuditProjectStatus::InProgress->value],
            AuditProjectStatus::InProgress->value => [AuditProjectStatus::Review->value],
            AuditProjectStatus::Review->value => [AuditProjectStatus::Final->value],
            AuditProjectStatus::Final->value => [AuditProjectStatus::Archived->value],
        ];

        if (! in_array($next->value, $allowed[$current->value] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Transisi status dari "%s" ke "%s" tidak diizinkan. Alur project: draft → team_setup → assignment → in_progress.',
                    $current->label(),
                    $next->label(),
                ),
            ]);
        }
    }

    private function addMember(AuditProject $project, User $member, ProjectRole $role, User $actor): AuditProjectMember
    {
        if ($project->members()->where('user_id', $member->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'User "'.$member->name.'" sudah terdaftar pada project ini.',
            ]);
        }

        $memberModel = $project->members()->create([
            'user_id' => $member->id,
            'project_role' => $role->value,
            'added_by' => $actor->id,
        ]);

        $this->log($project, $actor, 'member.added', 'Anggota "'.$member->name.'" ditambahkan sebagai '.$role->label().'.');

        return $memberModel;
    }

    private function log(AuditProject $project, User $actor, string $activity, string $description, ?array $meta = null): void
    {
        $project->activityLogs()->create([
            'user_id' => $actor->id,
            'activity' => $activity,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
