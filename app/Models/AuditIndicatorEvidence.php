<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_report_included' => 'boolean',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
