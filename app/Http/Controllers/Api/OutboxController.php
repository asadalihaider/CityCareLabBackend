<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Outbox\SendNotificationRequest;
use App\Jobs\ProcessOutboxJob;
use App\Models\Enum\OutboxChannel;

class OutboxController extends BaseApiController
{
    public function send(SendNotificationRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validated();

            ProcessOutboxJob::dispatch(
                mobile: $validated['mobile'],
                event: $validated['event'],
                data: $validated['data'] ?? [],
                channel: isset($validated['channel']) ? OutboxChannel::from($validated['channel']) : null,
            );

            return $this->successResponse(
                data: [
                    'status' => 'queued',
                    'channel' => $validated['channel'] ?? 'auto',
                ],
                message: 'Notification processed successfully.',
            );
        }, 'Failed to queue notification.');
    }
}
