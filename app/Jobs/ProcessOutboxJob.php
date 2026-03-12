<?php

namespace App\Jobs;

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
    ) {}

    public function handle(OutboxService $service): void
    {
        $service->send($this->mobile, $this->event, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOutboxJob: All retries exhausted.', [
            'mobile' => $this->mobile,
            'event' => $this->event,
            'data' => $this->data,
            'error' => $exception->getMessage(),
        ]);
    }
}
