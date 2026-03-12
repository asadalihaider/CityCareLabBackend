<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Outbox\SendNotificationRequest;
use App\Jobs\ProcessOutboxJob;

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
            );

            return $this->successResponse(
                data: ['status' => 'queued'],
                message: 'Notification processed successfully.',
            );
        }, 'Failed to queue notification.');
    }
}
