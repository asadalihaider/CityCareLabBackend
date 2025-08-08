<?php

namespace App\Models\Enum;

enum TestType: string
{
    use BaseEnum;

    case SINGLE = 'single';
    case PACKAGE = 'package';

    public function color(): string
    {
        return match ($this) {
            self::SINGLE => 'info',
            self::PACKAGE => 'success',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single Test',
            self::PACKAGE => 'Package',
        };
    }
}
