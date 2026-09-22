<?php

namespace App\Models;

use Database\Factories\AuditTemplatePrincipleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditTemplatePrinciple extends Model
{
    /** @use HasFactory<AuditTemplatePrincipleFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_template_id', 'code', 'title', 'description', 'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuditTemplate::class);
    }

    /** @return HasMany<AuditTemplateSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(AuditTemplateSection::class, 'principle_id')->orderBy('sort_order');
    }
}
