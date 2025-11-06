<?php

namespace App\Http\Requests\Booking;

use App\Models\Enum\BookingStatus;
use App\Models\Enum\BookingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        $bookingItemRule = Rule::requiredIf(in_array($this->input('booking_type'), [
            BookingType::TEST->value,
            BookingType::DISCOUNT_CARD->value,
        ]));

        return [
            'patient_name' => ['sometimes', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'string', 'max:20'],
            'booking_type' => ['sometimes', Rule::in(BookingType::values())],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:500'],
            'booking_items' => ['sometimes', $bookingItemRule, 'array'],
            'booking_items.*.id' => [$bookingItemRule, 'integer'],
            'booking_items.*.title' => [$bookingItemRule, 'string', 'max:255'],
            'booking_items.*.price' => ['bail', $bookingItemRule, 'numeric', 'min:0'],
            'booking_items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'location' => ['sometimes', 'nullable', 'array'],
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location.street_address' => ['nullable', 'string', 'max:500'],
            'location.city' => ['nullable', 'string', 'max:255'],
            'location.state' => ['nullable', 'string', 'max:255'],
            'location.postal_code' => ['nullable', 'string', 'max:20'],
            'location.country' => ['nullable', 'string', 'max:255'],
            'booking_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'status' => ['sometimes', Rule::in(BookingStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.max' => 'Patient name cannot exceed 255 characters',
            'contact_number.max' => 'Contact number cannot exceed 20 characters',
            'booking_type.in' => 'Invalid booking type selected',
            'purpose.max' => 'Purpose cannot exceed 500 characters',
            'booking_items.array' => 'Booking items must be an array',
            'booking_items.*.id.required_if' => 'Booking item must have a valid ID',
            'booking_items.*.id.integer' => 'Booking item ID must be a valid integer',
            'booking_items.*.id.exists' => 'The selected booking item does not exist',
            'booking_items.*.title.required_if' => 'Booking item must have a title',
            'booking_items.*.title.string' => 'The booking item title must be a valid string',
            'booking_items.*.title.max' => 'Booking item title cannot exceed 255 characters',
            'booking_items.*.price.required_if' => 'Booking item must have a price',
            'booking_items.*.price.numeric' => 'The booking item price must be a valid number',
            'booking_items.*.price.min' => 'Booking item price cannot be negative',
            'booking_items.*.discount.numeric' => 'The booking item discount must be a valid number',
            'booking_items.*.discount.min' => 'Booking item discount cannot be negative',
            'location.array' => 'Location must be an object',
            'location.latitude.between' => 'Latitude must be between -90 and 90',
            'location.longitude.between' => 'Longitude must be between -180 and 180',
            'location.street_address.max' => 'Street address cannot exceed 500 characters',
            'location.city.max' => 'City cannot exceed 255 characters',
            'location.state.max' => 'State cannot exceed 255 characters',
            'location.postal_code.max' => 'Postal code cannot exceed 20 characters',
            'location.country.max' => 'Country cannot exceed 255 characters',
            'status.in' => 'Invalid booking status',
            'booking_date.after_or_equal' => 'Booking date must be today or a future date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_name' => 'patient name',
            'contact_number' => 'contact number',
            'booking_type' => 'booking type',
            'booking_date' => 'booking date',
            'booking_items.*.id' => 'booking item ID',
            'booking_items.*.title' => 'booking item title',
            'booking_items.*.price' => 'booking item price',
            'booking_items.*.discount' => 'booking item discount',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes(
            'booking_items.*.id',
            'exists:tests,id',
            fn ($input) => $input->booking_type === BookingType::TEST->value
        );

        $validator->sometimes(
            'booking_items.*.id',
            'exists:discount_cards,id',
            fn ($input) => $input->booking_type === BookingType::DISCOUNT_CARD->value
        );

        $validator->after(function ($validator) {
            $location = $this->input('location', []);

            if (isset($location['latitude']) && ! isset($location['longitude'])) {
                $validator->errors()->add('location.longitude', 'Longitude is required when latitude is provided');
            }

            if (isset($location['longitude']) && ! isset($location['latitude'])) {
                $validator->errors()->add('location.latitude', 'Latitude is required when longitude is provided');
            }
        });
    }
}
