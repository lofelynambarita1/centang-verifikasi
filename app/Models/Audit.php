<?php

namespace App\Models;

use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'title', 'status', 'auditor_name', 'due_date', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditItem::class)->orderBy('sort_order');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }
}
