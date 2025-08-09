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
            'address' => ['sometimes', 'string', 'max:1000'],
            'booking_type' => ['sometimes', Rule::in(BookingType::values())],
            'purpose' => ['sometimes', 'nullable', 'string', 'max:500'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'booking_date' => ['sometimes', 'nullable', 'date', 'after:now'],
            'status' => ['sometimes', Rule::in(BookingStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.max' => 'Patient name cannot exceed 255 characters',
            'contact_number.max' => 'Contact number cannot exceed 20 characters',
            'address.max' => 'Address cannot exceed 1000 characters',
            'booking_type.in' => 'Invalid booking type selected',
            'purpose.max' => 'Purpose cannot exceed 500 characters',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.between' => 'Longitude must be between -180 and 180',
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
            // Add custom validation logic here if needed
            if ($this->filled('latitude') && ! $this->filled('longitude')) {
                $validator->errors()->add('longitude', 'Location is required');
            }

            if ($this->filled('longitude') && ! $this->filled('latitude')) {
                $validator->errors()->add('latitude', 'Location is required');
            }
        });
    }
}
