<?php

namespace App\Models\Enum;

enum FeedbackCategory: string
{
    use BaseEnum;

    case GENERAL = 'general';
    case SERVICE = 'service';
    case APP = 'app';
    case SUGGESTION = 'suggestion';
    case COMPLAINT = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General Feedback',
            self::SERVICE => 'Service Quality',
            self::APP => 'App Experience',
            self::SUGGESTION => 'Suggestion',
            self::COMPLAINT => 'Complaint',
        };
    }
}
