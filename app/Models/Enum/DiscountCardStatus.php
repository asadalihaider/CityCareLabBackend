<?php

namespace App\Models\Enum;

enum DiscountCardStatus: string
{
    use BaseEnum;

    case AVAILABLE = 'available';
    case ATTACHED = 'attached';
    case EXPIRED = 'expired';
    case DEACTIVATED = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::ATTACHED => 'Attached',
            self::EXPIRED => 'Expired',
            self::DEACTIVATED => 'Deactivated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'primary',
            self::ATTACHED => 'success',
            self::EXPIRED => 'danger',
            self::DEACTIVATED => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Card is available for attachment',
            self::ATTACHED => 'Card is currently attached to a customer',
            self::EXPIRED => 'Card has expired and cannot be used',
            self::DEACTIVATED => 'Card has been deactivated by admin',
        };
    }
}
