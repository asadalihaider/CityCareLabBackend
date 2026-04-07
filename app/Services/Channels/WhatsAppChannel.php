<?php

namespace App\Services\Channels;

use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Services\Channels\Data\ChannelSendResult;
use App\Support\PakistanMobile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements OutboxChannelContract
{
    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.whatsapp.enabled', false);
    }

    public function send(string $mobile, string $title, string $body, array $payload = []): ChannelSendResult
    {
        $canonical = PakistanMobile::normalize($mobile);

        if (! $canonical) {
            Log::warning('WhatsAppChannel: Invalid mobile format.', [
                'mobile' => $mobile,
            ]);

            return ChannelSendResult::fail('Invalid mobile format for WhatsApp delivery.');
        }

        $apiUrl = rtrim((string) config('services.whatsapp.api_url'), '/');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $token = $this->sanitizeAccessToken((string) config('services.whatsapp.access_token'));

        if ($apiUrl === '' || $phoneNumberId === '' || $token === '') {
            Log::error('WhatsAppChannel: Missing required WhatsApp config.', [
                'has_api_url' => $apiUrl !== '',
                'has_phone_number_id' => $phoneNumberId !== '',
                'has_access_token' => $token !== '',
            ]);

            return ChannelSendResult::fail('WhatsApp is not configured correctly.');
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $canonical,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => "*{$title}*\n\n{$body}",
                    ],
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $messageId = data_get($json, 'messages.0.id');
                $waId = data_get($json, 'contacts.0.wa_id');
                $note = 'Accepted by WhatsApp API';

                if ($messageId) {
                    $note .= "; message_id={$messageId}";
                }

                if ($waId) {
                    $note .= "; wa_id={$waId}";
                }

                return ChannelSendResult::ok($note);
            }

            $reason = "WhatsApp API responded with status {$response->status()}.";

            Log::warning('WhatsAppChannel: Non-2xx response.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return ChannelSendResult::fail($reason);
        } catch (\Throwable $e) {
            Log::error('WhatsAppChannel: Error.', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return ChannelSendResult::fail($e->getMessage());
        }
    }

    protected function sanitizeAccessToken(string $token): string
    {
        $clean = trim($token);

        return (string) preg_replace('/^Bearer\s+/i', '', $clean);
    }
}
