<?php

namespace App\Services;

use App\Models\Enum\OutboxChannel;
use App\Models\Enum\OutboxStatus;
use App\Models\OutboxLog;
use App\Services\Channels\Contracts\OutboxChannelContract;
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

    public function send(
        string $mobile,
        string $event,
        array $data = [],
        ?OutboxChannel $forceChannel = null,
        ?int $logId = null,
    ): void {
        $template = $this->resolveTemplate($event, $data);

        if (! $template) {
            $this->persistLog($logId, [
                'mobile' => $mobile,
                'event' => $event,
                'channel' => ($forceChannel ?? OutboxChannel::SMS)->value,
                'title' => null,
                'body' => null,
                'status' => OutboxStatus::FAILED->value,
                'response' => "Unknown event template: [{$event}]",
                'payload' => $data,
                'processed_at' => now(),
            ]);

            return;
        }

        ['title' => $title, 'body' => $body] = $template;

        // Forced channel — attempt only that one, update the pre-created log entry.
        if ($forceChannel !== null) {
            $handler = $this->handlerFor($forceChannel);
            $sent = $handler && $handler->isEnabled()
                ? $handler->send($mobile, $title, $body, $data)
                : false;

            $this->persistLog($logId, [
                'mobile' => $mobile,
                'event' => $event,
                'channel' => $forceChannel->value,
                'title' => $title,
                'body' => $body,
                'status' => ($sent ? OutboxStatus::SENT : OutboxStatus::FAILED)->value,
                'payload' => $data,
                'processed_at' => now(),
            ]);

            return;
        }

        // Cascade mode — Expo → WhatsApp → SMS, stop at first success.
        $channels = $this->getOrderedChannels();

        foreach ($channels as $index => ['enum' => $channelEnum, 'handler' => $handler]) {
            $sent = $handler->send($mobile, $title, $body, $data);

            $this->persistLog(null, [
                'mobile' => $mobile,
                'event' => $event,
                'channel' => $channelEnum->value,
                'title' => $title,
                'body' => $body,
                'status' => ($sent ? OutboxStatus::SENT : OutboxStatus::FAILED)->value,
                'payload' => $data,
                'processed_at' => now(),
            ]);

            if ($sent) {
                foreach (array_slice($channels, $index + 1) as ['enum' => $remainingEnum]) {
                    $this->persistLog(null, [
                        'mobile' => $mobile,
                        'event' => $event,
                        'channel' => $remainingEnum->value,
                        'title' => $title,
                        'body' => $body,
                        'status' => OutboxStatus::SKIPPED->value,
                        'payload' => $data,
                        'processed_at' => now(),
                    ]);
                }

                return;
            }
        }
    }

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

    protected function handlerFor(OutboxChannel $channel): ?OutboxChannelContract
    {
        return match ($channel) {
            OutboxChannel::EXPO => $this->expoPushChannel,
            OutboxChannel::WHATSAPP => $this->whatsAppChannel,
            OutboxChannel::SMS => $this->smsChannel,
        };
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

    protected function persistLog(?int $logId, array $attributes): void
    {
        try {
            if ($logId && $log = OutboxLog::find($logId)) {
                $log->update($attributes);
            } else {
                OutboxLog::create($attributes);
            }
        } catch (\Throwable $e) {
            Log::error('OutboxService: Failed to persist log entry.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
