<?php

namespace App\Console\Commands;

use App\Models\Enum\OutboxChannel;
use App\Models\OutboxLog;
use App\Services\OutboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOutboxCommand extends Command
{
    protected $signature = 'outbox:process {--limit=100 : Maximum logs to process per run}';

    protected $description = 'Process pending notifications from outbox queue';

    public function __construct(protected OutboxService $outboxService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = $this->option('limit');
        $count = 0;

        $logs = OutboxLog::whereNull('processed_at')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhereDate('scheduled_at', '<=', now());
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->line('No pending notifications to process.');

            return self::SUCCESS;
        }

        foreach ($logs as $log) {
            try {
                $channel = $log->preferred_channel ? OutboxChannel::from($log->preferred_channel) : null;

                $this->outboxService->send(
                    mobile: $log->mobile,
                    event: $log->event,
                    title: $log->title,
                    body: $log->body,
                    data: is_array($log->payload) ? $log->payload : [],
                    channel: $channel,
                    logId: $log->id,
                );

                $count++;
            } catch (\Throwable $e) {
                Log::error('ProcessOutboxCommand: Failed to process log', [
                    'log_id' => $log->id,
                    'mobile' => $log->mobile,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to process log {$log->id}: ".$e->getMessage());
            }
        }

        $this->info("Successfully processed {$count} outbox message(s).");

        return self::SUCCESS;
    }
}
