<?php

namespace App\Enums;

enum AuditProgressState: string
{
    case NotStarted = 'not_started';
    case StatusPending = 'status_pending';
    case DraftComplete = 'draft_complete';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Belum Dikerjakan',
            self::StatusPending => 'Status Belum Dipilih',
            self::DraftComplete => 'Draft Lengkap',
            self::Submitted => 'Submitted / Menunggu Review',
        };
    }
}
