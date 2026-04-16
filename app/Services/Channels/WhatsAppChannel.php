<?php

namespace App\Services\Channels;

use App\Models\Enum\NotificationEvent;
use App\Services\Channels\Concerns\ResolvesMessagePayload;
use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Services\Channels\Data\ChannelSendResult;
use App\Support\PakistanMobile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements OutboxChannelContract
{
    use ResolvesMessagePayload;

    public function isEnabled(): bool
    {
        return (bool) config('outbox.channels.whatsapp.enabled', false);
    }

    public function send(string $mobile, array $payload = []): ChannelSendResult
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
            $messageData = $this->buildMessagePayload($payload);

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", array_merge([
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $canonical,
                ], $messageData));

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

    private function buildMessagePayload(array $payload): array
    {
        $event = data_get($payload, 'event');

        return match ($event) {
            NotificationEvent::OTP->value => $this->buildOtpTemplate($payload),
            NotificationEvent::NEW_BOOKING->value => $this->buildNewBookingTemplate($payload),
            NotificationEvent::REPORT_READY->value => $this->buildReportReadyTemplate($payload),
            default => $this->buildFreeFormMessage($payload),
        };
    }

    private function buildOtpTemplate(array $payload): array
    {
        return [
            'type' => 'template',
            'template' => [
                'name' => 'otp_msg',
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => data_get($payload, 'otp_code', '000000')],
                            ['type' => 'text', 'text' => data_get($payload, 'action', 'CityCareLab')],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => data_get($payload, 'otp_code', '000000'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildNewBookingTemplate(array $payload): array
    {
        return [
            'type' => 'template',
            'template' => [
                'name' => 'new_booking_notify',
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => data_get($payload, 'customer_name', 'Customer')],
                            ['type' => 'text', 'text' => data_get($payload, 'case_id', 'N/A')],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => data_get($payload, 'case_id', 'N/A'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildReportReadyTemplate(array $payload): array
    {
        return [
            'type' => 'template',
            'template' => [
                'name' => 'report_ready_notify',
                'language' => [
                    'code' => 'en_US',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => data_get($payload, 'customer_name', 'Customer')],
                            ['type' => 'text', 'text' => data_get($payload, 'case_id', 'N/A')],
                        ],
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => data_get($payload, 'case_id', 'N/A'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildFreeFormMessage(array $payload): array
    {
        $title = (string) data_get($payload, 'title', '');
        $body = (string) data_get($payload, 'body', '');

        return [
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $title ? "*{$title}*\n{$body}" : $body,
            ],
        ];
    }
}
