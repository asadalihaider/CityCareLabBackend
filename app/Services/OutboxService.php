<?php

namespace App\Services;

use App\Models\Enum\OutboxChannel;
use App\Models\Enum\OutboxStatus;
use App\Models\OutboxLog;
use App\Services\Channels\ExpoPushChannel;
use App\Services\Channels\SmsChannel;
use App\Services\Channels\WhatsAppChannel;
use Illuminate\Support\Facades\Log;

class OutboxService
{
    public function __construct(
        protected ExpoPushChannel $expoPushChannel,
        protected WhatsAppChannel $whatsAppChannel,
        protected SmsChannel $smsChannel,
    ) {}

    /**
     * Resolve the event template and attempt delivery through prioritized channels.
     * Priority: Expo → WhatsApp → SMS. Stops at the first successful delivery.
     * Every attempt — including skipped channels — is persisted to outbox_logs.
     */
    public function send(string $mobile, string $event, array $data = []): void
    {
        $template = $this->resolveTemplate($event, $data);

        if (! $template) {
            $this->writeLog(
                mobile: $mobile,
                event: $event,
                channel: OutboxChannel::SMS,
                title: null,
                body: null,
                status: OutboxStatus::FAILED,
                response: "Unknown event template: [{$event}]",
                payload: $data,
            );

            return;
        }

        ['title' => $title, 'body' => $body] = $template;

        $channels = $this->getOrderedChannels();

        foreach ($channels as $index => ['enum' => $channelEnum, 'handler' => $handler]) {
            $sent = $handler->send($mobile, $title, $body, $data);

            $this->writeLog(
                mobile: $mobile,
                event: $event,
                channel: $channelEnum,
                title: $title,
                body: $body,
                status: $sent ? OutboxStatus::SENT : OutboxStatus::FAILED,
                payload: $data,
            );

            if ($sent) {
                $remaining = array_slice($channels, $index + 1);
                foreach ($remaining as ['enum' => $remainingEnum]) {
                    $this->writeLog(
                        mobile: $mobile,
                        event: $event,
                        channel: $remainingEnum,
                        title: $title,
                        body: $body,
                        status: OutboxStatus::SKIPPED,
                        payload: $data,
                    );
                }

                return;
            }
        }
    }

    /**
     * @return array{title: string, body: string}|null
     */
    public function resolveTemplate(string $event, array $data = []): ?array
    {
        $template = config("outbox.templates.{$event}");

        if (! $template) {
            Log::warning("OutboxService: No template registered for event [{$event}].");

            return null;
        }

        return [
            'title' => $this->interpolate($template['title'], $data),
            'body' => $this->interpolate($template['body'], $data),
        ];
    }

    protected function interpolate(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace("#{$key}", (string) $value, $text);
        }

        return $text;
    }

    protected function getOrderedChannels(): array
    {
        $all = [
            ['enum' => OutboxChannel::EXPO,     'handler' => $this->expoPushChannel],
            ['enum' => OutboxChannel::WHATSAPP, 'handler' => $this->whatsAppChannel],
            ['enum' => OutboxChannel::SMS,      'handler' => $this->smsChannel],
        ];

        return array_values(
            array_filter($all, fn (array $entry) => $entry['handler']->isEnabled())
        );
    }

    protected function writeLog(
        string $mobile,
        string $event,
        OutboxChannel $channel,
        ?string $title,
        ?string $body,
        OutboxStatus $status,
        ?string $response = null,
        array $payload = [],
    ): void {
        try {
            OutboxLog::create([
                'mobile' => $mobile,
                'event' => $event,
                'channel' => $channel->value,
                'title' => $title,
                'body' => $body,
                'status' => $status->value,
                'response' => $response,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('OutboxService: Failed to write log entry.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
