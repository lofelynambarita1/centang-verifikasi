<?php

namespace App\Enums;

enum SystemRole: string
{
    case Admin = 'admin';
    case Auditor = 'auditor';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isAuditor(): bool
    {
        return $this === self::Auditor;
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Auditor => 'Auditor',
        };
    }
}
