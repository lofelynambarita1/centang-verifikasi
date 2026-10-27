<?php

namespace App\Enums;

enum ProjectRole: string
{
    case LeadAudit = 'lead_audit';
    case LeadAuditor = 'lead_auditor';
    case Auditor = 'auditor';

    public function isLead(): bool
    {
        return in_array($this, [self::LeadAudit, self::LeadAuditor], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::LeadAudit, self::LeadAuditor => 'Lead Auditor',
            self::Auditor => 'Auditor',
        };
    }
}
