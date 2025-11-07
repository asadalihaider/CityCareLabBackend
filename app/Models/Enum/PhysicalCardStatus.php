<?php

namespace App\Models\Enum;

enum PhysicalCardStatus: string
{
    use BaseEnum;

    case AVAILABLE = 'available';
    case ACTIVATED = 'activated';
    case EXPIRED = 'expired';
    case DEACTIVATED = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::ACTIVATED => 'Activated',
            self::EXPIRED => 'Expired',
            self::DEACTIVATED => 'Deactivated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'primary',
            self::ACTIVATED => 'success',
            self::EXPIRED => 'danger',
            self::DEACTIVATED => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Card is available for activation',
            self::ACTIVATED => 'Card is currently activated by a customer',
            self::EXPIRED => 'Card has expired and cannot be used',
            self::DEACTIVATED => 'Card has been deactivated by admin',
        };
    }
}
