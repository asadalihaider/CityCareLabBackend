<?php

namespace App\Models\Enum;

enum BookingType: string
{
    use BaseEnum;

    case TEST = 'test';
    case CONSULTATION = 'consultation';
    case HEALTH_CARD = 'health_card';

    public function label(): string
    {
        return match ($this) {
            self::TEST => 'Test',
            self::CONSULTATION => 'Consultation',
            self::HEALTH_CARD => 'Health Card',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TEST => 'primary',
            self::CONSULTATION => 'success',
            self::HEALTH_CARD => 'warning',
        };
    }
}
