<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'role', 'requested_role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_principle_user')
            ->withPivot('principle_number')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLeadAudit(): bool
    {
        return $this->role === 'lead_audit';
    }

    public function canManageAssignments(): bool
    {
        return $this->isAdmin() || $this->isLeadAudit();
    }

    public function canReviewAudit(Audit $audit): bool
    {
        if ($this->canManageAssignments()) {
            return true;
        }

        if ($audit->relationLoaded('assignees')) {
            return $audit->assignees->contains('id', $this->id);
        }

        return $this->assignedPrincipleNumbers($audit)->isNotEmpty();
    }

    /**
     * @return Collection<int, int>
     */
    public function assignedPrincipleNumbers(Audit $audit): Collection
    {
        return $this->audits()
            ->where('audits.id', $audit->id)
            ->pluck('audit_principle_user.principle_number')
            ->map(fn (mixed $number): int => (int) $number)
            ->unique()
            ->sort()
            ->values();
    }

    public function canWorkOn(AuditItem $auditItem): bool
    {
        if ($this->canManageAssignments()) {
            return true;
        }

        return $this->audits()
            ->where('audits.id', $auditItem->audit_id)
            ->wherePivot('principle_number', $auditItem->principleNumber())
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
