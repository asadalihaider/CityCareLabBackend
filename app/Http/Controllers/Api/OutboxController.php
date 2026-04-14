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

            OutboxLog::create([
                'mobile' => $validated['mobile'],
                'event' => 'API_CLIENT',
                'title' => $validated['title'],
                'body' => $validated['body'],
                'preferred_channel' => isset($validated['channel']) && $validated['channel'] !== 'auto' ? $validated['channel'] : null,
                'payload' => $validated['data'] ?? [],
            ]);

            return $this->successResponse(null, 'Notification queued successfully.');

        }, 'Failed to queue notification.');
    }
}
