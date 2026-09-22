<?php

namespace App\Enums;

enum AuditProgressState: string
{
    case NotStarted = 'not_started';
    case StatusPending = 'status_pending';
    case DraftComplete = 'draft_complete';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Belum Dikerjakan',
            self::StatusPending => 'Status Belum Dipilih',
            self::DraftComplete => 'Draft Lengkap',
            self::Submitted => 'Submitted / Menunggu Review',
            self::RevisionRequested => 'Revision Requested',
            self::Approved => 'Approved',
        };
    }
}
