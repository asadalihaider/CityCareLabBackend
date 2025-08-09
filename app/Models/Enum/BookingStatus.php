<?php

namespace App\Models\Enum;

use Filament\Support\Colors\Color;

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

    public function color(): string
    {
        return match ($this) {
            self::WAITING => 'gray',
            self::VERIFIED => Color::Lime,
            self::ON_THE_WAY => 'primary',
            self::SAMPLE_COLLECTED => Color::Teal,
            self::IN_PROCESS => Color::Violet,
            self::COMPLETED => 'success',
            self::DECLINED => 'danger',
        };
    }
}
