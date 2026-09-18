<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditHasil extends Model
{
    protected $fillable = ['audit_item_id', 'sort_order', 'content', 'evidence_path'];

    public function auditItem(): BelongsTo
    {
        return $this->belongsTo(AuditItem::class);
    }
}
