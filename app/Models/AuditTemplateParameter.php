<?php

namespace App\Models;

use App\Enums\ParameterType;
use Database\Factories\AuditTemplateParameterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTemplateParameter extends Model
{
    /** @use HasFactory<AuditTemplateParameterFactory> */
    use HasFactory;

    protected $fillable = [
        'indicator_id', 'parameter_type', 'number', 'content', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['parameter_type' => ParameterType::class];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditTemplateIndicator::class, 'indicator_id');
    }
}
