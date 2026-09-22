<?php

namespace App\Models;

use Database\Factories\AuditTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditTemplate extends Model
{
    /** @use HasFactory<AuditTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'code', 'scheme', 'name', 'version', 'description', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AuditProject, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(AuditProject::class, 'template_id');
    }

    /** @return HasMany<AuditTemplatePrinciple, $this> */
    public function principles(): HasMany
    {
        return $this->hasMany(AuditTemplatePrinciple::class)->orderBy('sort_order');
    }
}
