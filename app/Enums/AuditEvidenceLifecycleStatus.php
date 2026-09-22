<?php

namespace App\Enums;

enum AuditEvidenceLifecycleStatus: string
{
    case Active = 'active';
    case Superseded = 'superseded';
    case Removed = 'removed';
}
