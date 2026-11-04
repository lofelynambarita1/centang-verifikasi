<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
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
}
