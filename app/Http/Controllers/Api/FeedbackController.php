<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Customer;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;

class FeedbackController extends BaseApiController
{
    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            /** @var Customer $customer */
            $customer = auth('sanctum')->user();

            $validatedData = $request->validated();
            $validatedData['customer_id'] = $customer->id;

            Feedback::create($validatedData);

            return $this->createdResponse(null, 'Thank you for your feedback! We appreciate your input.');
        }, 'Failed to submit feedback');
    }
}
