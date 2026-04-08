<?php

namespace App\Services;

use App\Models\Enum\OutboxChannel;
use App\Models\OutboxLog;
use App\Services\Channels\Contracts\OutboxChannelContract;
use App\Services\Channels\Data\ChannelSendResult;
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
                'title' => null,
                'body' => null,
                'response' => "Unknown event template: [{$event}]",
                'payload' => $data,
                'attempts' => [
                    [
                        'channel' => null,
                        'status' => 'failed',
                        'reason' => "Unknown event template: [{$event}]",
                        'timestamp' => now()->format('M d, Y h:i A'),
                    ],
                ],
                'processed_at' => now(),
            ]);

            return;
        }

        ['title' => $title, 'body' => $body] = $template;

        if ($forceChannel !== null) {
            $handler = $this->handlerFor($forceChannel);
            $result = $handler->isEnabled()
                ? $handler->send($mobile, $title, $body, $data)
                : ChannelSendResult::fail('Selected channel is disabled.');

            $attempt = [
                'channel' => $forceChannel->value,
                'status' => $result->success ? 'sent' : 'failed',
                'reason' => $result->reason ?: ($result->success ? 'Delivered' : 'Delivery failed'),
                'timestamp' => now()->format('M d, Y h:i A'),
            ];

            $this->persistLog($logId, [
                'mobile' => $mobile,
                'event' => $event,
                'title' => $title,
                'body' => $body,
                'response' => $result->reason,
                'payload' => $data,
                'attempts' => [$attempt],
                'processed_at' => now(),
            ]);

            return;
        }

        $channels = $this->getOrderedChannels();

        if (empty($channels)) {
            $this->persistLog($logId, [
                'mobile' => $mobile,
                'event' => $event,
                'title' => $title,
                'body' => $body,
                'response' => 'No messaging channels are enabled. Please configure at least one channel.',
                'payload' => $data,
                'attempts' => [
                    [
                        'channel' => null,
                        'status' => 'failed',
                        'reason' => 'No messaging channels are enabled.',
                        'timestamp' => now()->format('M d, Y h:i A'),
                    ],
                ],
                'processed_at' => now(),
            ]);

            return;
        }

        $attempts = [];

        foreach ($channels as $index => ['enum' => $channelEnum, 'handler' => $handler]) {
            $result = $handler->send($mobile, $title, $body, $data);

            $attempt = [
                'channel' => $channelEnum->value,
                'status' => $result->success ? 'sent' : 'failed',
                'reason' => $result->reason ?: ($result->success ? 'Delivered' : 'Delivery failed'),
                'timestamp' => now()->format('M d, Y h:i A'),
            ];

            $attempts[] = $attempt;

            // If success, stop trying other channels
            if ($result->success) {
                break;
            }
        }

        $this->persistLog($logId, [
            'mobile' => $mobile,
            'event' => $event,
            'title' => $title,
            'body' => $body,
            'response' => $this->summarizeAttempts($attempts),
            'payload' => $data,
            'attempts' => $attempts,
            'processed_at' => now(),
        ]);
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
            $replace = (string) $value;
            $text = str_replace("#{{$key}}", $replace, $text);
            $text = str_replace("#{$key}", $replace, $text);
        }

        return $text;
    }

    protected function handlerFor(OutboxChannel $channel): OutboxChannelContract
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

    protected function summarizeAttempts(array $attempts): string
    {
        if (empty($attempts)) {
            return 'No attempts recorded.';
        }

        return collect($attempts)
            ->map(fn ($a) => ucfirst($a['channel'] ?? 'system').": {$a['status']}")
            ->implode(' → ');
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
