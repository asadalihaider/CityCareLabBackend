<?php

namespace App\Models\Enum;

enum BookingType: string
{
    use BaseEnum;

    case TEST = 'test';
    case CONSULTATION = 'consultation';
    case DISCOUNT_CARD = 'discount_card';

    public function label(): string
    {
        return match ($this) {
            self::TEST => 'Test',
            self::CONSULTATION => 'Consultation',
            self::DISCOUNT_CARD => 'Discount Card',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TEST => 'primary',
            self::CONSULTATION => 'success',
            self::DISCOUNT_CARD => 'warning',
        };
    }
}
