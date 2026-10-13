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
        'audit_project_id', 'audit_project_indicator_id', 'result_text', 'result_content', 'assessment_status', 'created_by', 'updated_by', 'lock_version',
        'workflow_status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'assessment_status' => AuditAssessmentStatus::class,
            'result_content' => 'array',
            'lock_version' => 'integer',
            'workflow_status' => AuditResultWorkflowStatus::class,
            'submitted_at' => 'datetime',
            'submitted_by' => 'integer',
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<AuditResultReviewEvent, $this> */
    public function reviewEvents(): HasMany
    {
        return $this->hasMany(AuditResultReviewEvent::class, 'audit_indicator_result_id')->orderBy('id');
    }

    /** @return HasMany<AuditIndicatorResultVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(AuditIndicatorResultVersion::class, 'audit_indicator_result_id');
    }

    /** @return HasMany<AuditResultBlock, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(AuditResultBlock::class, 'audit_indicator_result_id')->orderBy('sort_order')->orderBy('id');
    }
}
