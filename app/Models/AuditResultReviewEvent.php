<?php

namespace App\Models;

use App\Enums\AuditResultReviewAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditResultReviewEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'audit_project_id',
        'audit_project_indicator_id',
        'audit_indicator_result_id',
        'action',
        'note',
        'actor_id',
        'content_lock_version',
        'audit_indicator_result_version_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditResultReviewAction::class,
            'content_lock_version' => 'integer',
            'audit_indicator_result_version_id' => 'integer',
            'created_at' => 'datetime',
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

    public function result(): BelongsTo
    {
        return $this->belongsTo(AuditIndicatorResult::class, 'audit_indicator_result_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function resultVersion(): BelongsTo
    {
        return $this->belongsTo(AuditIndicatorResultVersion::class, 'audit_indicator_result_version_id');
    }
}
