<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Outbox\SendNotificationRequest;
use App\Models\OutboxLog;

class OutboxController extends BaseApiController
{
    public function send(SendNotificationRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validated();
            $payload = $validated['data'];

            OutboxLog::create([
                'mobile' => $validated['mobile'],
                'event' => 'API_CLIENT',
                'title' => $payload['title'] ?? null,
                'body' => $payload['body'] ?? null,
                'preferred_channel' => isset($validated['channel']) && $validated['channel'] !== 'auto' ? $validated['channel'] : null,
                'payload' => $payload,
            ]);

            return $this->successResponse(null, 'Notification queued successfully.');

        }, 'Failed to queue notification.');
    }
}
