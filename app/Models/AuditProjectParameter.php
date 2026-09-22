<?php

namespace App\Models;

use App\Enums\ParameterType;
use Database\Factories\AuditProjectParameterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditProjectParameter extends Model
{
    /** @use HasFactory<AuditProjectParameterFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_project_id', 'audit_project_indicator_id', 'source_template_parameter_id', 'parameter_type', 'number', 'content', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['parameter_type' => ParameterType::class];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AuditProject::class, 'audit_project_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(AuditProjectIndicator::class, 'audit_project_indicator_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AuditTemplateParameter::class, 'source_template_parameter_id');
    }
}
