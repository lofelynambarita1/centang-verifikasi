<?php

namespace App\Models;

use App\Enums\AuditProjectStatus;
use App\Enums\ProjectRole;
use Database\Factories\AuditProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditProject extends Model
{
    /** @use HasFactory<AuditProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'project_code',
        'template_id',
        'company_id',
        'name',
        'audit_type',
        'audit_start_date',
        'audit_end_date',
        'land_area',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuditProjectStatus::class,
            'audit_start_date' => 'date',
            'audit_end_date' => 'date',
            'land_area' => 'decimal:2',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuditTemplate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AuditProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(AuditProjectMember::class);
    }

    public function leadMember(): HasOne
    {
        return $this->hasOne(AuditProjectMember::class)
            ->where('project_role', ProjectRole::LeadAuditor->value);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_project_members')
            ->withPivot('project_role', 'added_by')
            ->withTimestamps();
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    /** @return HasMany<AuditProjectPrinciple, $this> */
    public function snapshotPrinciples(): HasMany
    {
        return $this->hasMany(AuditProjectPrinciple::class)->orderBy('sort_order');
    }

    /** @return HasMany<AuditProjectSection, $this> */
    public function snapshotSections(): HasMany
    {
        return $this->hasMany(AuditProjectSection::class)->orderBy('sort_order');
    }

    /** @return HasMany<AuditProjectIndicator, $this> */
    public function snapshotIndicators(): HasMany
    {
        return $this->hasMany(AuditProjectIndicator::class)->orderBy('sort_order');
    }

    /** @return HasMany<AuditProjectIndicator, $this> */
    public function indicators(): HasMany
    {
        return $this->hasMany(AuditProjectIndicator::class, 'audit_project_id');
    }

    /** @return HasMany<AuditProjectParameter, $this> */
    public function snapshotParameters(): HasMany
    {
        return $this->hasMany(AuditProjectParameter::class)->orderBy('sort_order');
    }

    /** @return HasMany<AuditIndicatorAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(AuditIndicatorAssignment::class, 'audit_project_id');
    }

    /** @return HasMany<AuditAssignmentHistory, $this> */
    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(AuditAssignmentHistory::class, 'audit_project_id');
    }

    /** @return HasMany<AuditIndicatorResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(AuditIndicatorResult::class, 'audit_project_id');
    }

    /** @return HasMany<AuditIndicatorResultVersion, $this> */
    public function resultVersions(): HasMany
    {
        return $this->hasMany(AuditIndicatorResultVersion::class, 'audit_project_id');
    }

    public function userIsLead(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('project_role', ProjectRole::LeadAuditor->value)
            ->exists();
    }

    public function unassignedIndicatorCount(): int
    {
        return $this->snapshotIndicators()
            ->whereDoesntHave('assignment')
            ->count();
    }
}
