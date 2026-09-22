<?php

namespace App\Models;

use Database\Factories\AuditProjectSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditProjectSection extends Model
{
    /** @use HasFactory<AuditProjectSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'audit_project_principle_id', 'parent_id', 'source_template_section_id', 'code', 'title', 'description', 'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function principle(): BelongsTo
    {
        return $this->belongsTo(AuditProjectPrinciple::class, 'audit_project_principle_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AuditTemplateSection::class, 'source_template_section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<AuditProjectIndicator, $this> */
    public function indicators(): HasMany
    {
        return $this->hasMany(AuditProjectIndicator::class, 'audit_project_section_id')->orderBy('sort_order');
    }
}
