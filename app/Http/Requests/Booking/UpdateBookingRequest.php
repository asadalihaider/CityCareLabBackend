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
        return [
            'patient_name' => ['sometimes', 'string', 'max:255'],
            'contact_number' => ['sometimes', 'string', 'max:20'],
            'booking_type' => ['sometimes', Rule::in(BookingType::values())],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:500'],
            'booking_items' => ['sometimes', 'nullable', 'array'],
            'booking_items.*.test_id' => ['required_if:booking_items.*.type,test', 'integer', 'exists:tests,id'],
            'location' => ['sometimes', 'nullable', 'array'],
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location.street_address' => ['nullable', 'string', 'max:500'],
            'location.city' => ['nullable', 'string', 'max:255'],
            'location.state' => ['nullable', 'string', 'max:255'],
            'location.postal_code' => ['nullable', 'string', 'max:20'],
            'location.country' => ['nullable', 'string', 'max:255'],
            'booking_date' => ['sometimes', 'nullable', 'date', 'after:now'],
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
            'booking_items.*.test_id.required_if' => 'Test ID is required for test items',
            'booking_items.*.test_id.exists' => 'Selected test does not exist',
            'location.array' => 'Location must be an object',
            'location.latitude.between' => 'Latitude must be between -90 and 90',
            'location.longitude.between' => 'Longitude must be between -180 and 180',
            'location.street_address.max' => 'Street address cannot exceed 500 characters',
            'location.city.max' => 'City cannot exceed 255 characters',
            'location.state.max' => 'State cannot exceed 255 characters',
            'location.postal_code.max' => 'Postal code cannot exceed 20 characters',
            'location.country.max' => 'Country cannot exceed 255 characters',
            'booking_date.after' => 'Booking date must be in the future',
            'status.in' => 'Invalid booking status',
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_name' => 'patient name',
            'contact_number' => 'contact number',
            'booking_type' => 'booking type',
            'booking_date' => 'booking date',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate location coordinates are provided together
            $location = $this->input('location', []);
            
            if (isset($location['latitude']) && !isset($location['longitude'])) {
                $validator->errors()->add('location.longitude', 'Longitude is required when latitude is provided');
            }

            if (isset($location['longitude']) && !isset($location['latitude'])) {
                $validator->errors()->add('location.latitude', 'Latitude is required when longitude is provided');
            }
        });
    }
}
