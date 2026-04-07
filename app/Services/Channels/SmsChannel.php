<?php

namespace App\Services\Channels;

use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Services\Channels\Data\ChannelSendResult;
use App\Support\PakistanMobile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel implements OutboxChannelContract
{
    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.sms.enabled', false);
    }

    public function send(string $mobile, string $title, string $body, array $payload = []): ChannelSendResult
    {
        $canonical = PakistanMobile::normalize($mobile);

        if (! $canonical) {
            Log::warning('SmsChannel: Invalid mobile format.', [
                'mobile' => $mobile,
            ]);

            return ChannelSendResult::fail('Invalid mobile format for SMS delivery.');
        }

        $apiUrl = config('services.bsms.api_url', '/');
        $apiKey = config('services.bsms.api_key');
        $senderId = config('services.bsms.sender_id');

        $message = trim("{$title}\n{$body}");

        try {
            $response = Http::timeout(15)
                ->get($apiUrl, [
                    'key' => $apiKey,
                    'sender' => $senderId,
                    'receiver' => $canonical,
                    'msgdata' => $message,
                    'response_type' => 'json',
                ]);

            $data = $response->json();

            if (isset($data['error_no']) && (int) $data['error_no'] === 0) {
                return ChannelSendResult::ok();
            }

            $reason = isset($data['error_message'])
                ? (string) $data['error_message']
                : 'SMS provider rejected the request.';

            Log::warning('SmsChannel: Delivery rejected by BSMS.', [
                'mobile' => $mobile,
                'response' => $data,
            ]);

            return ChannelSendResult::fail($reason);
        } catch (\Throwable $e) {
            Log::error('SmsChannel: Exception during delivery.', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return ChannelSendResult::fail($e->getMessage());
        }
    }
}
