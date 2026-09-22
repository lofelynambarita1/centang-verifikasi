<?php

namespace App\Models;

use Database\Factories\AuditProjectIndicatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuditProjectIndicator extends Model
{
    /** @use HasFactory<AuditProjectIndicatorFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'audit_project_section_id', 'source_template_indicator_id', 'code', 'title', 'description', 'indicator_type', 'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AuditProjectSection::class, 'audit_project_section_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AuditTemplateIndicator::class, 'source_template_indicator_id');
    }

    /** @return HasMany<AuditProjectParameter, $this> */
    public function parameters(): HasMany
    {
        return $this->hasMany(AuditProjectParameter::class, 'audit_project_indicator_id')->orderBy('sort_order');
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(AuditIndicatorAssignment::class, 'audit_project_indicator_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(AuditIndicatorResult::class, 'audit_project_indicator_id');
    }
}
