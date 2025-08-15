<?php

namespace App\Models\Enum;

enum OtpType: string
{
    use BaseEnum;

    case FORGOT_PASSWORD = 'forgot_password';
    case MOBILE_VERIFICATION = 'mobile_verification';
    case EMAIL_VERIFICATION = 'email_verification';

    public function label(): string
    {
        return match ($this) {
            self::FORGOT_PASSWORD => 'Forgot Password',
            self::MOBILE_VERIFICATION => 'Mobile Verification',
            self::EMAIL_VERIFICATION => 'Email Verification',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FORGOT_PASSWORD => 'warning',
            self::MOBILE_VERIFICATION => 'primary',
            self::EMAIL_VERIFICATION => 'info',
        };
    }
}
