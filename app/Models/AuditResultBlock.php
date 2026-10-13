<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditResultBlock extends Model
{
    public const TypeListItem = 'list_item';

    public const MaxLevel = 3;

    protected $fillable = [
        'audit_project_id',
        'audit_project_indicator_id',
        'audit_indicator_result_id',
        'type',
        'level',
        'content',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'created_by' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
