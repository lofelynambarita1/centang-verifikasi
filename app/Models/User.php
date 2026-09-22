<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'system_role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'system_role' => SystemRole::class,
        ];
    }

    /**
     * @return HasMany<AuditProject, $this>
     */
    public function createdAuditProjects(): HasMany
    {
        return $this->hasMany(AuditProject::class, 'created_by');
    }

    /**
     * @return HasMany<AuditProjectMember, $this>
     */
    public function auditProjectMemberships(): HasMany
    {
        return $this->hasMany(AuditProjectMember::class);
    }

    /** @return HasMany<AuditIndicatorEvidence, $this> */
    public function uploadedAuditEvidences(): HasMany
    {
        return $this->hasMany(AuditIndicatorEvidence::class, 'uploaded_by');
    }

    /**
     * @return BelongsToMany<AuditProject, $this>
     */
    public function auditProjects(): BelongsToMany
    {
        return $this->belongsToMany(AuditProject::class, 'audit_project_members')
            ->withPivot('project_role', 'added_by')
            ->withTimestamps();
    }

    /**
     * @return HasMany<AuditIndicatorAssignment, $this>
     */
    public function picAssignments(): HasMany
    {
        return $this->hasMany(AuditIndicatorAssignment::class, 'pic_user_id');
    }

    /**
     * @return HasMany<AuditIndicatorResult, $this>
     */
    public function createdResults(): HasMany
    {
        return $this->hasMany(AuditIndicatorResult::class, 'created_by');
    }

    /**
     * @return HasMany<AuditIndicatorResult, $this>
     */
    public function updatedResults(): HasMany
    {
        return $this->hasMany(AuditIndicatorResult::class, 'updated_by');
    }

    /**
     * @return HasMany<AuditIndicatorResultVersion, $this>
     */
    public function changedResultVersions(): HasMany
    {
        return $this->hasMany(AuditIndicatorResultVersion::class, 'changed_by');
    }
}
