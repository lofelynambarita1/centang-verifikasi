<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'title', 'status', 'auditor_name', 'due_date', 'start_date', 'end_date', 'scope', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditItem::class)->orderBy('sort_order');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_principle_user')
            ->withPivot('principle_number')
            ->withTimestamps();
    }

    public function assigneesForPrinciple(int $principleNumber): BelongsToMany
    {
        return $this->assignees()->wherePivot('principle_number', $principleNumber);
    }

    /**
     * @return array<int, string>
     */
    public function auditorNames(): array
    {
        if (blank($this->auditor_name)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|,/', $this->auditor_name))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values()
            ->all();
    }

    public function auditorNamesText(): string
    {
        return implode(', ', $this->auditorNames());
    }

    public function members(): HasMany
    {
        return $this->hasMany(AuditProjectMember::class, 'audit_id');
    }

    public function projectMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_project_members', 'audit_id', 'user_id')
            ->withPivot(['project_role', 'added_by'])
            ->withTimestamps();
    }

    /**
     * Get the team auditors (Lead Auditor and Auditor members) assigned to this project.
     * Excludes admins and users with pending approval.
     *
     * @return Collection<int, User>
     */
    public function teamAuditors()
    {
        if ($this->relationLoaded('projectMembers')) {
            return $this->projectMembers
                ->filter(fn (User $user): bool => ! $user->isAdmin() && $user->requested_role === null)
                ->sortBy('name')
                ->values();
        }

        if ($this->relationLoaded('members')) {
            return $this->members
                ->map(fn (AuditProjectMember $member): ?User => $member->user)
                ->filter(fn (?User $user): bool => $user !== null && ! $user->isAdmin() && $user->requested_role === null)
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return $this->projectMembers()
            ->whereNull('users.requested_role')
            ->where('users.role', '!=', 'admin')
            ->orderBy('users.name')
            ->get();
    }

    public function leadMember(): HasOne
    {
        return $this->hasOne(AuditProjectMember::class, 'audit_id')
            ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value]);
    }

    public function userIsLead(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value])
            ->exists();
    }

    public function userIsMember(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function userCanAccessReport(User $user): bool
    {
        return $user->isAdmin()
            || $this->userIsMember($user)
            || $this->assignees()->whereKey($user->getKey())->exists();
    }
}
