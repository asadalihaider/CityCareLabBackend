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
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        ?OutboxChannel $channel = null,
        ?int $logId = null,
    ): void {
        $title = $this->resolveString($title ?? $data['title'] ?? null);
        $body = $this->resolveString($body ?? $data['body'] ?? null);

        if (! $title || ! $body) {
            if ($logId && $log = OutboxLog::find($logId)) {
                $title ??= $log->title;
                $body ??= $log->body;
                $channel ??= $log->preferred_channel ? OutboxChannel::from($log->preferred_channel) : null;
            }
        }

        $attempts = ($title && $body)
            ? ($channel ? $this->tryChannel($mobile, $title, $body, $data, $channel) : $this->tryCascade($mobile, $title, $body, $data))
            : [['channel' => null, 'status' => 'failed', 'reason' => 'Missing title or body', 'timestamp' => now()]];

        $this->persistLog($logId, compact('mobile', 'event', 'title', 'body', 'data') + [
            'response' => $this->summarize($attempts),
            'payload' => $data,
            'attempts' => $attempts,
            'processed_at' => now(),
        ]);
    }

    private function resolveString(?string $value): ?string
    {
        return $value && trim($value) ? trim($value) : null;
    }

    private function tryChannel(string $mobile, string $title, string $body, array $data, OutboxChannel $channel): array
    {
        $handler = $this->getHandler($channel);
        $result = $handler->isEnabled()
            ? $handler->send($mobile, $title, $body, $data)
            : ChannelSendResult::fail('Channel disabled');

        return [$this->recordAttempt($channel->value, $result)];
    }

    private function tryCascade(string $mobile, string $title, string $body, array $data): array
    {
        $handlers = [
            OutboxChannel::EXPO => $this->expoPushChannel,
            OutboxChannel::WHATSAPP => $this->whatsAppChannel,
            OutboxChannel::SMS => $this->smsChannel,
        ];

        $enabled = array_filter($handlers, fn ($h) => $h->isEnabled());

        if (empty($enabled)) {
            return [['channel' => null, 'status' => 'failed', 'reason' => 'No channels enabled', 'timestamp' => now()]];
        }

        $attempts = [];
        foreach ($enabled as $channel => $handler) {
            /** @var OutboxChannel $channel */
            $result = $handler->send($mobile, $title, $body, $data);
            $attempts[] = $this->recordAttempt($channel->value, $result);
            if ($result->success) {
                break;
            }
        }

        return $attempts;
    }

    private function recordAttempt(string $channel, ChannelSendResult $result): array
    {
        return [
            'channel' => $channel,
            'status' => $result->success ? 'sent' : 'failed',
            'reason' => $result->reason ?: ($result->success ? 'Delivered' : 'Failed'),
            'timestamp' => now(),
        ];
    }

    private function getHandler(OutboxChannel $channel): OutboxChannelContract
    {
        return match ($channel) {
            OutboxChannel::EXPO => $this->expoPushChannel,
            OutboxChannel::WHATSAPP => $this->whatsAppChannel,
            OutboxChannel::SMS => $this->smsChannel,
        };
    }

    private function summarize(array $attempts): string
    {
        return collect($attempts)
            ->map(fn ($a) => ucfirst($a['channel'] ?? 'system').':'.$a['status'])
            ->implode(' → ') ?: 'Never attempted.';
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
            Log::error('OutboxService: Failed to persist log.', ['error' => $e->getMessage()]);
        }
    }
}
