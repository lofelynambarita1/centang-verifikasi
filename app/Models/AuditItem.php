<?php

namespace App\Models;

use Database\Factories\AuditItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditItem extends Model
{
    /** @use HasFactory<AuditItemFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_id', 'sort_order', 'title', 'description', 'status', 'note', 'evidence_path',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
