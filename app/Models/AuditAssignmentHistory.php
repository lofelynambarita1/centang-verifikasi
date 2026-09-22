<?php

namespace App\Models;

use Database\Factories\AuditAssignmentHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditAssignmentHistory extends Model
{
    /** @use HasFactory<AuditAssignmentHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'audit_project_id', 'audit_project_indicator_id', 'from_user_id', 'to_user_id', 'changed_by', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
