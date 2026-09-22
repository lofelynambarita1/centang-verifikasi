<?php

namespace App\Models;

use Database\Factories\AuditTemplateSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditTemplateSection extends Model
{
    /** @use HasFactory<AuditTemplateSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'principle_id', 'parent_id', 'code', 'title', 'description', 'sort_order',
    ];

    public function principle(): BelongsTo
    {
        return $this->belongsTo(AuditTemplatePrinciple::class);
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

    /** @return HasMany<AuditTemplateIndicator, $this> */
    public function indicators(): HasMany
    {
        return $this->hasMany(AuditTemplateIndicator::class, 'section_id')->orderBy('sort_order');
    }
}
