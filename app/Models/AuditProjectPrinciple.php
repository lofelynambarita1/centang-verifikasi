<?php

namespace App\Models;

use Database\Factories\AuditProjectPrincipleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditProjectPrinciple extends Model
{
    /** @use HasFactory<AuditProjectPrincipleFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'source_template_principle_id', 'code', 'title', 'description', 'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AuditTemplatePrinciple::class, 'source_template_principle_id');
    }

    /** @return HasMany<AuditProjectSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(AuditProjectSection::class, 'audit_project_principle_id')->orderBy('sort_order');
    }
}
