<?php

namespace App\Services\Channels;

use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Support\PakistanMobile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements OutboxChannelContract
{
    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.whatsapp.enabled', false);
    }

    public function send(string $mobile, string $title, string $body, array $payload = []): bool
    {
        $canonical = PakistanMobile::normalize($mobile);

        if (! $canonical) {
            Log::warning('WhatsAppChannel: Invalid mobile format.', [
                'mobile' => $mobile,
            ]);

            return false;
        }

        $apiUrl = rtrim((string) config('services.whatsapp.api_url'), '/');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.access_token');

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $canonical,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => "*{$title}*\n\n{$body}",
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsAppChannel: Non-2xx response.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsAppChannel: Error.', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
