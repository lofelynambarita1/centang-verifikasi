<?php

namespace App\Models;

use Database\Factories\AuditFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends Model
{
    /** @use HasFactory<AuditFindingFactory> */
    use HasFactory;

    protected $fillable = ['audit_id', 'note', 'recommendation'];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
