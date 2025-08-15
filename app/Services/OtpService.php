<?php

namespace App\Services;

use App\Models\Enum\OtpType;
use App\Models\Otp;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected function sendSms(string $mobileNumber, string $otp, OtpType $type): bool
    {
        try {
            // TODO: Integrate with SMS service provider (e.g., Twilio, SMS service, etc.)
            // For now, we'll just log the OTP for testing purposes
            Log::info("SMS OTP sent to {$mobileNumber}: {$otp} (Type: {$type->label()})");

            // Mock successful send
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP to {$mobileNumber}: " . $e->getMessage());
            return false;
        }
    }

    protected function sendEmail(string $email, string $otp, OtpType $type): bool
    {
        try {
            // TODO: Integrate with email service (Laravel Mail, etc.)
            // For now, we'll just log the OTP for testing purposes
            Log::info("Email OTP sent to {$email}: {$otp} (Type: {$type->label()})");

            // Mock successful send
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Email OTP to {$email}: " . $e->getMessage());
            return false;
        }
    }

    public function sendOtp(string $identifier, string $otp, OtpType $type): bool
    {
        try {
            // Determine delivery method based on identifier format
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                return $this->sendEmail($identifier, $otp, $type);
            } else {
                return $this->sendSms($identifier, $otp, $type);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$identifier}: " . $e->getMessage());
            return false;
        }
    }

    public function createAndSendOtp(string $identifier, OtpType $type): ?Otp
    {
        try {
            $otp = Otp::createForIdentifier($identifier, $type);

            $sent = $this->sendOtp($identifier, $otp->otp, $type);

            if (!$sent) {
                $otp->delete();
                return null;
            }

            return $otp;
        } catch (\Exception $e) {
            Log::error("Failed to create and send OTP: " . $e->getMessage());
            return null;
        }
    }

    public function verifyOtp(string $identifier, string $otpCode, OtpType $type): array
    {
        $otp = Otp::forIdentifier($identifier)
            ->ofType($type)
            ->valid()
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP. Please request a new one.'
            ];
        }

        if ($otp->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Maximum attempts exceeded. Please request a new OTP.'
            ];
        }

        if ($otp->otp !== $otpCode) {
            $otp->incrementAttempts();
            return [
                'success' => false,
                'message' => 'Invalid OTP. Please try again.'
            ];
        }

        $otp->markAsVerified();

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
            'otp' => $otp
        ];
    }

    public function cleanExpiredOtps(): int
    {
        return Otp::where('expires_at', '<', now())->delete();
    }
}
