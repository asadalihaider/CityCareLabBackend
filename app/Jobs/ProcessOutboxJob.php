<?php

namespace App\Jobs;

use App\Models\Enum\OutboxChannel;
use App\Services\OutboxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOutboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 60;

    public function __construct(
        public readonly string $mobile,
        public readonly string $event,
        public readonly array $data = [],
        public readonly ?OutboxChannel $channel = null,
        public readonly ?int $outboxLogId = null,
    ) {}

    public function handle(OutboxService $service): void
    {
        $service->send($this->mobile, $this->event, $this->data, $this->channel, $this->outboxLogId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOutboxJob: All retries exhausted.', [
            'mobile' => $this->mobile,
            'event' => $this->event,
            'outbox_log_id' => $this->outboxLogId,
            'error' => $exception->getMessage(),
        ]);
    }
}
