<?php

namespace App\Models;

use App\Enums\AuditAssessmentStatus;
use Database\Factories\AuditIndicatorResultVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIndicatorResultVersion extends Model
{
    /** @use HasFactory<AuditIndicatorResultVersionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'audit_indicator_result_id', 'audit_project_id', 'audit_project_indicator_id', 'version_number', 'result_text', 'assessment_status', 'changed_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'assessment_status' => AuditAssessmentStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AuditIndicatorResult::class, 'audit_indicator_result_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function changedBy(): BelongsTo
    {
        return $this->changer();
    }
}
