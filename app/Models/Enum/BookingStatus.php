<?php

namespace App\Models\Enum;

enum BookingStatus: string
{
    use BaseEnum;

    case WAITING = 'waiting';
    case VERIFIED = 'verified';
    case ON_THE_WAY = 'on_the_way';
    case SAMPLE_COLLECTED = 'sample_collected';
    case IN_PROCESS = 'in_process';
    case COMPLETED = 'completed';
    case DECLINED = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::VERIFIED => 'Verified',
            self::ON_THE_WAY => 'Rider On The Way',
            self::SAMPLE_COLLECTED => 'Sample Collected',
            self::IN_PROCESS => 'In Process',
            self::COMPLETED => 'Completed',
            self::DECLINED => 'Declined',
        };
    }

    public static function getOptionsForBookingType(BookingType $bookingType): array
    {
        return match ($bookingType) {
            BookingType::CONSULTATION => collect([
                self::WAITING,
                self::VERIFIED,
                self::COMPLETED,
                self::DECLINED,
            ])->mapWithKeys(fn ($status) => [$status->value => $status->label()])->toArray(),

            BookingType::TEST => self::toOptions(),
        };
    }
}
