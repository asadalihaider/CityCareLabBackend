<?php

namespace App\Http\Requests\Customer;

use App\Models\Enum\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->user();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:operating_cities,id'],
            'image' => ['sometimes', 'nullable', 'file'],
            'dob' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', Rule::enum(Gender::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken by another customer.',
            'location_id.integer' => 'Location must be valid.',
            'location_id.exists' => 'The selected location does not exist.',
            'dob.date' => 'Date of birth must be a valid date.',
            'dob.before' => 'Date of birth must be before today.',
            'image.file' => 'Image must be a valid file.',
            'image.image' => 'File must be an image.',
            'image.max' => 'Image size cannot exceed 2MB.',
            'gender.in' => 'Gender must be either male or female.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('mobile_number')) {
                $validator->errors()->add(
                    'mobile_number',
                    'Phone number cannot be changed. Please contact support if you need to update your phone number.'
                );
            }
        });
    }
}
