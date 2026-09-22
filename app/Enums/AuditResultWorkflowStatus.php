<?php

namespace App\Enums;

enum AuditResultWorkflowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'SUBMITTED / MENUNGGU REVIEW',
            self::RevisionRequested => 'REVISI DIMINTA',
            self::Approved => 'APPROVED',
        };
    }
}
