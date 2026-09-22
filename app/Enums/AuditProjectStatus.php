<?php

namespace App\Enums;

enum AuditProjectStatus: string
{
    case Draft = 'draft';
    case TeamSetup = 'team_setup';
    case Assignment = 'assignment';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Final = 'final';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::TeamSetup => 'Penyusunan Tim',
            self::Assignment => 'Penugasan Indikator',
            self::InProgress => 'Berjalan',
            self::Review => 'Review',
            self::Final => 'Final',
            self::Archived => 'Diarsipkan',
        };
    }
}
