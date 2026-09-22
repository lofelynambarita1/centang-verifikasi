<?php

namespace App\Models;

use Database\Factories\AuditTemplateIndicatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditTemplateIndicator extends Model
{
    /** @use HasFactory<AuditTemplateIndicatorFactory> */
    use HasFactory;

    protected $fillable = [
        'section_id', 'code', 'title', 'description', 'indicator_type', 'sort_order',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(AuditTemplateSection::class);
    }

    /** @return HasMany<AuditTemplateParameter, $this> */
    public function parameters(): HasMany
    {
        return $this->hasMany(AuditTemplateParameter::class, 'indicator_id')->orderBy('sort_order');
    }
}
