<?php

namespace App\Services\Channels\Concerns;

use App\Models\Enum\NotificationEvent;

trait ResolvesMessagePayload
{
    protected function resolveMessagePart($value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function resolveTitleAndBody(array $payload): array
    {
        $event = data_get($payload, 'event');
        $eventMessage = $this->resolveEventMessage($event, $payload);

        if ($eventMessage) {
            return [
                'title' => $this->resolveMessagePart($eventMessage['title'] ?? null),
                'body' => $this->resolveMessagePart($eventMessage['body'] ?? null),
            ];
        }

        return [
            'title' => $this->resolveMessagePart($payload['title'] ?? null),
            'body' => $this->resolveMessagePart($payload['body'] ?? null),
        ];
    }

    protected function resolveEventMessage(?string $event, array $payload): ?array
    {
        if (! is_string($event) || trim($event) === '') {
            return null;
        }

        return match (trim($event)) {
            NotificationEvent::OTP->value => $this->buildOtpMessage($payload),
            NotificationEvent::NEW_BOOKING->value => $this->buildNewBookingMessage($payload),
            NotificationEvent::REPORT_READY->value => $this->buildReportReadyMessage($payload),
            default => null,
        };
    }

    private function buildOtpMessage(array $payload): array
    {
        $otpCode = (string) data_get($payload, 'otp_code', '000000');
        $action = (string) data_get($payload, 'action', 'CityCareLab');

        return [
            'title' => 'Verification Code',
            'body' => "Your {$action} verification code is {$otpCode}. It is valid for 10 minutes. Do not share it with anyone.",
        ];
    }

    private function buildNewBookingMessage(array $payload): array
    {
        $customerName = (string) data_get($payload, 'customer_name', 'Valued Customer');
        $caseId = (string) data_get($payload, 'case_id', '');

        return [
            'title' => 'Booking Confirmed',
            'body' => "Hi {$customerName}, your booking {$caseId} has been confirmed.",
        ];
    }

    private function buildReportReadyMessage(array $payload): array
    {
        $customerName = (string) data_get($payload, 'customer_name', 'Valued Customer');

        return [
            'title' => 'Report Ready',
            'body' => "Hi {$customerName}, your lab report is ready for download.",
        ];
    }
}
