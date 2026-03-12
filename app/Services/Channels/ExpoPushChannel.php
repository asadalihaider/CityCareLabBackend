<?php

namespace App\Services\Channels;

use App\Models\Customer;
use App\Notifications\PushNotification;
use App\Services\Channels\Contracts\OutboxChannelContract;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel implements OutboxChannelContract
{
    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.expo.enabled', true);
    }

    public function send(string $mobile, string $title, string $body, array $payload = []): bool
    {
        $customer = Customer::where('mobile_number', $mobile)
            ->whereHas('expoTokens')
            ->first();

        if (! $customer) {
            Log::debug('ExpoPushChannel: No customer with active Expo tokens found.', [
                'mobile' => $mobile,
            ]);

            return false;
        }

        try {
            $customer->notify(new PushNotification(
                title: $title,
                body: $body,
                data: $payload,
                shouldBatch: false,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('ExpoPushChannel: Delivery failed.', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
