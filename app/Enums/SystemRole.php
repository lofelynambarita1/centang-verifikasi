<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Auditor = 'auditor';

    public function isAdminOrAbove(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Auditor => 'Auditor',
        };
    }
}
