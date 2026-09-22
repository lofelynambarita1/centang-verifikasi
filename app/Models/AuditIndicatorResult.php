<?php

namespace App\Models;

use App\Enums\AuditAssessmentStatus;
use App\Enums\AuditResultWorkflowStatus;
use Database\Factories\AuditIndicatorResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditIndicatorResult extends Model
{
    /** @use HasFactory<AuditIndicatorResultFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'audit_project_indicator_id', 'result_text', 'assessment_status', 'created_by', 'updated_by', 'lock_version',
        'workflow_status', 'submitted_at', 'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'assessment_status' => AuditAssessmentStatus::class,
            'lock_version' => 'integer',
            'workflow_status' => AuditResultWorkflowStatus::class,
            'submitted_at' => 'datetime',
            'submitted_by' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return HasMany<AuditIndicatorResultVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(AuditIndicatorResultVersion::class, 'audit_indicator_result_id');
    }
}
