<?php

namespace App\Models;

use App\Enums\CertificationCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationProcess extends Model
{
    protected $fillable = [
        'company_id',
        'category',
        'summary_document_path',
        'audit_correction_document_path',
        'audit_report',
        'review_panel',
        'certificate_document_path',
        'certificate_token',
    ];

    protected function casts(): array
    {
        return [
            'category' => CertificationCategory::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
