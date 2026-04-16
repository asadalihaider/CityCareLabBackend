<?php

namespace App\Models\Enum;

enum NotificationEvent: string
{
    use BaseEnum;

    case OTP = 'OTP';
    case NEW_BOOKING = 'NEW_BOOKING';
    case REPORT_READY = 'REPORT_READY';

    public function label(): string
    {
        return match ($this) {
            self::OTP => 'One-Time Password',
            self::NEW_BOOKING => 'New Booking',
            self::REPORT_READY => 'Report Ready',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OTP => 'OTP verification notification',
            self::NEW_BOOKING => 'Booking confirmation notification',
            self::REPORT_READY => 'Lab report ready notification',
        };
    }

    /**
     * Get events that require customer_name and case_id.
     */
    public static function eventsRequiringCustomerData(): array
    {
        return [
            self::NEW_BOOKING->value,
            self::REPORT_READY->value,
        ];
    }
}
