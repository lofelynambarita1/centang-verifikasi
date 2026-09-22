<?php

namespace App\Enums;

enum AuditAssessmentStatus: string
{
    case Memenuhi = 'memenuhi';
    case TidakMemenuhi = 'tidak_memenuhi';
    case Na = 'na';

    public function label(): string
    {
        return match ($this) {
            self::Memenuhi => 'Memenuhi',
            self::TidakMemenuhi => 'Tidak Memenuhi',
            self::Na => 'NA',
        };
    }
}
