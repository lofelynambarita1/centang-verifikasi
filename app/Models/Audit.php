<?php

namespace App\Models;

use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    /** @use HasFactory<AuditFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'title', 'status', 'auditor_name', 'due_date',
        'report_summary_paragraph_one', 'report_summary_paragraph_two', 'completed_at',
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

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_principle_user')
            ->withPivot('principle_number')
            ->withTimestamps();
    }

    public function assigneesForPrinciple(int $principleNumber): BelongsToMany
    {
        return $this->assignees()->wherePivot('principle_number', $principleNumber);
    }

    /**
     * @return array<int, string>
     */
    public function auditorNames(): array
    {
        if (blank($this->auditor_name)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n|,/', $this->auditor_name))
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->values()
            ->all();
    }

    public function auditorNamesText(): string
    {
        return implode(', ', $this->auditorNames());
    }

    public function reportSummaryParagraphOne(): string
    {
        $default = str_replace(
            ':company',
            $this->company?->name ?: 'perusahaan',
            (string) config('ispo.report_summary.paragraph_one'),
        );

        return filled($this->report_summary_paragraph_one)
            ? $this->report_summary_paragraph_one
            : $default;
    }

    public function reportSummaryParagraphTwo(): string
    {
        return filled($this->report_summary_paragraph_two)
            ? $this->report_summary_paragraph_two
            : (string) config('ispo.report_summary.paragraph_two');
    }
}
