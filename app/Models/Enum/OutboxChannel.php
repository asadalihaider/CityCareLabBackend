<?php

namespace App\Models\Enum;

enum OutboxChannel: string
{
    use BaseEnum;

    case EXPO = 'expo';
    case WHATSAPP = 'whatsapp';
    case SMS = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::EXPO => 'Expo Push',
            self::WHATSAPP => 'WhatsApp',
            self::SMS => 'SMS',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EXPO => 'info',
            self::WHATSAPP => 'success',
            self::SMS => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EXPO => 'heroicon-o-device-phone-mobile',
            self::WHATSAPP => 'heroicon-o-chat-bubble-left-right',
            self::SMS => 'heroicon-o-chat-bubble-left',
        };
    }
}
