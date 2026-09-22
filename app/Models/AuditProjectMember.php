<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\AuditProjectMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditProjectMember extends Model
{
    /** @use HasFactory<AuditProjectMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'user_id', 'project_role', 'added_by',
    ];

    protected function casts(): array
    {
        return ['project_role' => ProjectRole::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
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
        return $this->project_role === ProjectRole::LeadAuditor;
    }
}
