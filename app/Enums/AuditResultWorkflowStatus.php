<?php

namespace App\Enums;

enum AuditResultWorkflowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'SUBMITTED / MENUNGGU REVIEW',
        };
    }
}
