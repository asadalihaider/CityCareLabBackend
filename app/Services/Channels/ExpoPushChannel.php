<?php

namespace App\Services\Channels;

use App\Models\Customer;
use App\Notifications\PushNotification;
use App\Services\Channels\Concerns\ResolvesMessagePayload;
use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Services\Channels\Data\ChannelSendResult;
use App\Support\PakistanMobile;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel implements OutboxChannelContract
{
    use ResolvesMessagePayload;

    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.expo.enabled', false);
    }

    public function send(string $mobile, array $payload = []): ChannelSendResult
    {
        $canonical = PakistanMobile::normalize($mobile);

        if (! $canonical) {
            Log::debug('ExpoPushChannel: Invalid mobile format.', [
                'mobile' => $mobile,
            ]);

            return ChannelSendResult::fail('Invalid mobile format for Expo push.');
        }

        $title = $this->resolveMessagePart($payload['title'] ?? null);
        $body = $this->resolveMessagePart($payload['body'] ?? null);

        if (! $title || ! $body) {
            return ChannelSendResult::fail('Expo payload must contain non-empty title and body.');
        }

        try {
            $customer = Customer::where('mobile_number', $canonical)
                ->whereHas('expoTokens')
                ->first();

            if (! $customer) {
                Log::debug('ExpoPushChannel: No customer with active Expo tokens found.', [
                    'mobile' => $mobile,
                ]);

                return ChannelSendResult::fail('No customer with active Expo tokens found.');
            }

            $customer->notify(new PushNotification(
                title: $title,
                body: $body,
                data: $payload,
                shouldBatch: false,
            ));

            return ChannelSendResult::ok('Push notification sent via Expo.');
        } catch (\Throwable $e) {
            Log::error('ExpoPushChannel: Delivery failed.', [
                'mobile' => $mobile,
                'code' => $e->getCode(),
                'error' => $e->getMessage(),
            ]);

            if ($e->getCode() === '42S02') {
                return ChannelSendResult::fail('Mobile app not installed or not registered for push notifications.');
            }

            return ChannelSendResult::fail($e->getMessage());
        }
    }
}
