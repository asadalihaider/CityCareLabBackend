<?php

namespace App\Models\Enum;

enum FeedbackStatus: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case DISMISSED = 'dismissed';
    case IN_PROCESS = 'in_process';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::DISMISSED => 'Dismissed',
            self::DRAFT => 'Draft',
            self::IN_PROCESS => 'In Process',
            self::RESOLVED => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'primary',
            self::DISMISSED => 'gray',
            self::IN_PROCESS => 'info',
            self::RESOLVED => 'success',
        };
    }
}
