<?php

namespace App\Enums;

enum ProjectRole: string
{
    case LeadAuditor = 'lead_auditor';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::LeadAuditor => 'Lead Auditor',
            self::Auditor => 'Auditor',
        };
    }
}
