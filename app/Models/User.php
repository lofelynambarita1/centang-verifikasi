<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ProjectRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'role_id', 'requested_role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->role_id && empty($user->attributes['role'])) {
                $user->role = Role::find($user->role_id)?->name ?? 'auditor';
            } elseif (! empty($user->attributes['role']) && ! $user->role_id) {
                $user->role_id = Role::where('name', $user->attributes['role'])->value('id');
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function roleRelation(): BelongsTo
    {
        return $this->role();
    }

    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_principle_user')
            ->withPivot('principle_number')
            ->withTimestamps();
    }

    public function auditMemberships(): HasMany
    {
        return $this->hasMany(AuditProjectMember::class, 'user_id');
    }

    public function auditProjects(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_project_members', 'user_id', 'audit_id')
            ->withPivot(['project_role', 'added_by'])
            ->withTimestamps();
    }

    public function roleName(): ?string
    {
        return $this->attributes['role'] ?? (is_object($this->role) ? $this->role->name : null);
    }

    public function isAdmin(): bool
    {
        return $this->roleName() === 'admin';
    }

    public function isAuditor(): bool
    {
        return $this->roleName() === 'auditor';
    }

    public function isLeadAuditor(?Audit $audit = null): bool
    {
        if ($audit !== null) {
            return $audit->userIsLead($this) || $this->roleName() === 'lead_audit';
        }

        return $this->roleName() === 'lead_audit'
            || $this->auditMemberships()
                ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value, 'lead_audit', 'lead_auditor'])
                ->exists();
    }

    public function isLeadAudit(?Audit $audit = null): bool
    {
        return $this->isLeadAuditor($audit);
    }

    public function canManageAssignments(?Audit $audit = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($audit !== null) {
            return $audit->userIsLead($this) || $this->roleName() === 'lead_audit';
        }

        return $this->isLeadAuditor();
    }

    public function canWorkOn(AuditItem $auditItem): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        $audit = $auditItem->audit;

        // Lead auditor pada audit terkait berhak mengisi checklist verifikasi
        if ($this->isLeadAuditor($audit)) {
            return true;
        }

        // Auditor anggota proyek berhak jika ditugaskan pada prinsip audit tersebut
        return $this->audits()
            ->where('audits.id', $auditItem->audit_id)
            ->wherePivot('principle_number', $auditItem->principleNumber())
            ->exists();
    }

    public function canUpdateChecklist(AuditItem $auditItem): bool
    {
        return $this->isAdmin() || $this->canWorkOn($auditItem);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
