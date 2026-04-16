<?php

namespace App\Services;

use App\Models\Enum\OtpType;
use App\Models\Enum\OutboxChannel;
use App\Models\Otp;
use App\Models\OutboxLog;
use App\Services\Channels\SmsChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function __construct(
        protected SmsChannel $smsChannel,
    ) {}

    protected function sendSms(string $mobileNumber, string $otp, OtpType $type): bool
    {
        try {
            $sent = $this->smsChannel->send(
                mobile: $mobileNumber,
                payload: [
                    'otp_type' => $type->value,
                    'otp_code' => $otp,
                    'event' => 'OTP',
                ],
            );

            $this->logToOutbox($mobileNumber, $otp, $type, $sent);

            return $sent->success;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP to {$mobileNumber}: ".$e->getMessage());

            return false;
        }
    }

    private function logToOutbox(string $mobile, string $otp, OtpType $type, $result): void
    {
        try {
            $existingLog = OutboxLog::where('mobile', $mobile)
                ->where('event', 'SYSTEM')
                ->where('payload->otp_type', $type->value)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->latest('created_at')
                ->first();

            $attempt = [
                'channel' => OutboxChannel::SMS->value,
                'status' => $result->success ? 'sent' : 'failed',
                'reason' => $result->reason ?: ($result->success ? 'Delivered' : 'Failed'),
                'timestamp' => now(),
            ];

            if ($existingLog) {
                $attempts = $existingLog->attempts ?? [];
                $attempts[] = $attempt;

                $existingLog->update([
                    'response' => $result->reason ?: ($result->success ? 'Delivered' : 'Failed'),
                    'attempts' => $attempts,
                    'processed_at' => now(),
                ]);
            } else {
                OutboxLog::create([
                    'mobile' => $mobile,
                    'event' => 'SYSTEM',
                    'response' => $result->reason ?: ($result->success ? 'Delivered' : 'Failed'),
                    'payload' => [
                        'otp_type' => $type->value,
                        'otp_code' => $otp,
                        'event' => 'OTP',
                    ],
                    'attempts' => [$attempt],
                    'processed_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('OtpService: Failed to log SMS to outbox.', [
                'mobile' => $mobile,
                'otp' => $otp,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendEmail(string $email, string $otp): bool
    {
        try {
            $subject = 'Your OTP Code';
            $message = "Your verification code is {$otp}. It is valid for 10 minutes. Do not share it with anyone.";

            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send Email OTP to {$email}: ".$e->getMessage());

            return false;
        }
    }

    public function sendOtp(string $identifier, string $otp, OtpType $type): bool
    {
        try {
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                return $this->sendEmail($identifier, $otp);
            } else {
                return $this->sendSms($identifier, $otp, $type);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$identifier}: ".$e->getMessage());

            return false;
        }
    }

    public function createAndSendOtp(string $identifier, OtpType $type): ?Otp
    {
        try {
            $otp = Otp::createForIdentifier($identifier, $type);

            $sent = $this->sendOtp($identifier, $otp->otp, $type);

            if (! $sent) {
                $otp->delete();

                return null;
            }

            return $otp;
        } catch (\Exception $e) {
            Log::error('Failed to create and send OTP: '.$e->getMessage());

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

        if (! $otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP. Please request a new one.',
            ];
        }

        if ($otp->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Maximum attempts exceeded. Please request a new OTP.',
            ];
        }

        if ($otp->otp !== $otpCode) {
            $otp->incrementAttempts();

            return [
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ];
        }

        $otp->markAsVerified();

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
            'otp' => $otp,
        ];
    }

    public function hasVerifiedOtp(string $identifier, string $otpCode, OtpType $type, int $withinMinutes = 5): array
    {
        $otp = Otp::forIdentifier($identifier)
            ->ofType($type)
            ->where('otp', $otpCode)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes($withinMinutes))
            ->latest('verified_at')
            ->first();

        if (! $otp) {
            return [
                'success' => false,
                'message' => 'No verified OTP found or verification expired. Please verify OTP first.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verified OTP found.',
            'otp' => $otp,
        ];
    }

    public function cleanExpiredOtps(): int
    {
        return Otp::where('expires_at', '<', now())->delete();
    }
}
