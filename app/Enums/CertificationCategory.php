<?php

namespace App\Enums;

enum CertificationCategory: string
{
    case Hulu = 'hulu';
    case Hilir = 'hilir';
    case Bio = 'bio';

    public function label(): string
    {
        return match ($this) {
            self::Hulu => 'Hulu',
            self::Hilir => 'Hilir',
            self::Bio => 'Bioenergi',
        };
    }
}
