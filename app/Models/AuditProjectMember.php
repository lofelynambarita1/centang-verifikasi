<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'audit_project_id',
        'user_id',
        'project_role',
        'added_by',
        'deadline_at',
        'first_submitted_at',
        'last_submitted_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (AuditProjectMember $member): void {
            if (! empty($member->audit_id) && empty($member->audit_project_id)) {
                $member->audit_project_id = $member->audit_id;
            } elseif (! empty($member->audit_project_id) && empty($member->audit_id)) {
                $member->audit_id = $member->audit_project_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'project_role' => ProjectRole::class,
            'deadline_at' => 'datetime',
            'first_submitted_at' => 'datetime',
            'last_submitted_at' => 'datetime',
        ];
    }

    public function isLate(string $auditStatus, ?CarbonInterface $now = null): bool
    {
        if ($this->deadline_at === null) {
            return false;
        }

        return ($this->last_submitted_at !== null && $this->last_submitted_at->greaterThan($this->deadline_at))
            || ($auditStatus !== 'completed' && ($now ?? now())->greaterThan($this->deadline_at));
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class, 'audit_id');
    }

    public function project(): BelongsTo
    {
        return $this->audit();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function isLead(): bool
    {
        return $this->project_role instanceof ProjectRole
            ? $this->project_role->isLead()
            : in_array($this->project_role, [ProjectRole::LeadAudit, ProjectRole::LeadAuditor, 'lead_audit', 'lead_auditor'], true);
    }
}
