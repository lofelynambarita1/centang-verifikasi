<?php

namespace App\Models;

use Database\Factories\AuditIndicatorAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIndicatorAssignment extends Model
{
    /** @use HasFactory<AuditIndicatorAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'audit_project_indicator_id', 'pic_user_id', 'assigned_by', 'assigned_at',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
