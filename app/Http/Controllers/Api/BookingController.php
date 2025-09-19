<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Enum\BookingType;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            /** @var Customer $customer */
            $customer = auth('sanctum')->user();
            $query = $customer->bookings()->with('customer:id,name');

            if ($request->has('status') && $request->status) {
                $query->byStatus($request->status);
            }

            if ($request->has('type') && $request->type) {
                $query->byType($request->type);
            }

            $bookings = $query->recent()->paginate(20);

            $bookings->getCollection()->transform(function ($booking) {
                return $this->transformBooking($booking);
            });

            return $this->paginatedResponse($bookings, 'Bookings retrieved successfully');
        }, 'Failed to retrieve bookings');
    }

    public function show(Booking $booking): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($booking) {
            /** @var Customer $customer */
            $customer = auth('sanctum')->user();

            if ($booking->customer_id !== $customer->id) {
                return $this->errorResponse('Booking not found', 404);
            }

            $booking->load('customer:id,name');

            return $this->successResponse($this->transformBooking($booking), 'Booking retrieved successfully');
        }, 'Failed to retrieve booking');
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            /** @var Customer $customer */
            $customer = auth('sanctum')->user();
            $booking = $customer->bookings()->create($request->validated());

            $booking->load('customer:id,name');

            return $this->createdResponse($this->transformBooking($booking), 'Booking created successfully');
        }, 'Failed to create booking');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        return $this->executeWithExceptionHandling(function () use ($request, $booking) {
            /** @var Customer $customer */
            $customer = auth('sanctum')->user();

            if ($booking->customer_id !== $customer->id) {
                return $this->errorResponse('Booking not found', 404);
            }

            $booking->update($request->validated());
            $booking->load('customer:id,name');

            return $this->updatedResponse($this->transformBooking($booking), 'Booking updated successfully');
        }, 'Failed to update booking');
    }

    private function transformBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'status' => $booking->status,
            'patientName' => $booking->patient_name,
            'contactNumber' => $booking->contact_number,
            'bookingType' => $booking->booking_type,
            'purpose' => $booking->purpose,
            'bookingItems' => $this->transformBookingItems($booking),
            'location' => $this->transformLocation($booking),
            'bookingDate' => $booking->booking_date?->toISOString(),
            'customer' => $booking->customer->name,
        ];
    }

    private function transformBookingItems(Booking $booking): array
    {
        if (!$booking->booking_items) {
            return [];
        }

        return collect($booking->booking_items)->map(function ($item) {
            if ($item['type'] === BookingType::TEST) {
                $testData = [];

                if (!empty($item['test_id'])) {
                    $test = Test::find($item['test_id']);
                    if ($test) {
                        $testData['test'] = [
                            'id' => $test->id,
                            'title' => $test->title,
                            'price' => $test->price,
                            'discount' => $test->discount,
                        ];
                    }
                }

                return $testData;
            }

            return $item;
        })->toArray();
    }

    private function transformLocation(Booking $booking): ?array
    {
        if (!$booking->location) {
            return null;
        }

        $location = $booking->location;
        
        return [
            'location' => [
                'street' => $location['street_address'] ?? null,
                'city' => $location['city'] ?? null,
                'state' => $location['state'] ?? null,
                'postalCode' => $location['postal_code'] ?? null,
                'country' => $location['country'] ?? null,
                'latitude' => $location['latitude'] ?? (float) $location['latitude'],
                'longitude' => $location['longitude'] ?? (float) $location['longitude'],
            ],
        ];
    }
}
