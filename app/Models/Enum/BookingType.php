<?php

namespace App\Models\Enum;

enum BookingType: string
{
    use BaseEnum;

    case TEST = 'test';
    case CONSULTATION = 'consultation';

    public function label(): string
    {
        return match ($this) {
            self::TEST => 'Test',
            self::CONSULTATION => 'Consultation',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TEST => 'primary',
            self::CONSULTATION => 'success',
        };
    }
}
