<?php

namespace App\Enums;

enum ParameterType: string
{
    case Parameter = 'parameter';
    case Verifier = 'verifier';

    public function label(): string
    {
        return match ($this) {
            self::Parameter => 'Parameter',
            self::Verifier => 'Verifier',
        };
    }
}
