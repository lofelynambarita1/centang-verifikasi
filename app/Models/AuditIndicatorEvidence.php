<?php

namespace App\Models;

use App\Enums\AuditEvidenceLifecycleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditIndicatorEvidence extends Model
{
    protected $table = 'audit_indicator_evidences';

    protected $fillable = [
        'audit_project_id',
        'audit_project_indicator_id',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'storage_disk',
        'storage_path',
        'uploaded_by',
        'caption',
        'sort_order',
        'is_report_included',
        'lifecycle_status',
        'replaces_evidence_id',
        'superseded_at',
        'removed_at',
        'removed_by',
        'removal_reason',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_report_included' => 'boolean',
            'lifecycle_status' => AuditEvidenceLifecycleStatus::class,
            'replaces_evidence_id' => 'integer',
            'removed_by' => 'integer',
            'superseded_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('lifecycle_status', AuditEvidenceLifecycleStatus::Active->value);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_evidence_id');
    }

    public function replacedBy(): HasMany
    {
        return $this->hasMany(self::class, 'replaces_evidence_id');
    }

    public function remover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }
}
