<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'industry', 'address', 'client_year', 'land_area', 'contact_name', 'contact_email',
    ];

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function latestAudit(): HasOne
    {
        return $this->hasOne(Audit::class)->latestOfMany();
    }

    public function certificationProcesses(): HasMany
    {
        return $this->hasMany(CertificationProcess::class);
    }

    public function canManageCertificationProcess(User $user): bool
    {
        return $user->isAdmin()
            || $user->roleName() === 'lead_audit'
            || $this->audits()
                ->whereHas('members', fn (Builder $query) => $query
                    ->where('user_id', $user->id)
                    ->whereIn('project_role', [ProjectRole::LeadAudit->value, ProjectRole::LeadAuditor->value]))
                ->exists();
    }
}
