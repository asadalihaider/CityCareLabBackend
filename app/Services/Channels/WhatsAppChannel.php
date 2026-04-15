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
            $messagePayload = $this->buildMessagePayload($canonical, $title, $body, $payload);

            if ($messagePayload === false) {
                return ChannelSendResult::fail('WhatsApp template payload is invalid or incomplete.');
            }

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", $messagePayload);

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

    protected function buildMessagePayload(string $to, string $title, string $body, array $payload): array|false
    {
        $basePayload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ];

        $templatePayload = $this->resolveTemplatePayload($payload);

        if ($templatePayload === false) {
            return false;
        }

        if ($templatePayload !== null) {
            return array_merge($basePayload, $templatePayload);
        }

        return array_merge($basePayload, [
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => "*{$title}*\n\n{$body}",
            ],
        ]);
    }

    protected function resolveTemplatePayload(array $payload): array|false|null
    {
        $template = $payload['template'] ?? null;

        if (is_array($template) && ! empty($template)) {
            if (! $this->isValidTemplateDefinition($template)) {
                Log::warning('WhatsAppChannel: Invalid inline template payload structure.', [
                    'has_name' => isset($template['name']),
                    'has_language_code' => isset($template['language']['code']),
                ]);

                return false;
            }

            return [
                'type' => 'template',
                'template' => $template,
            ];
        }

        $selector = $this->resolveTemplateSelector($payload);

        if ($selector === null) {
            return null;
        }

        $templates = $payload['templates'] ?? [];

        if (! is_array($templates) || ! isset($templates[$selector]) || ! is_array($templates[$selector])) {
            Log::warning('WhatsAppChannel: Template selector not found in payload.', [
                'template_selector' => $selector,
                'available_templates' => is_array($templates) ? array_keys($templates) : [],
            ]);

            return false;
        }

        if (! $this->isValidTemplateDefinition($templates[$selector])) {
            Log::warning('WhatsAppChannel: Selected template has invalid structure.', [
                'template_selector' => $selector,
                'has_name' => isset($templates[$selector]['name']),
                'has_language_code' => isset($templates[$selector]['language']['code']),
            ]);

            return false;
        }

        return [
            'type' => 'template',
            'template' => $templates[$selector],
        ];
    }

    protected function resolveTemplateSelector(array $payload): ?string
    {
        foreach (['event', 'template_name', 'template'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    protected function isValidTemplateDefinition(array $template): bool
    {
        $name = $template['name'] ?? null;
        $languageCode = $template['language']['code'] ?? null;

        return is_string($name)
            && trim($name) !== ''
            && is_string($languageCode)
            && trim($languageCode) !== '';
    }
}
