<?php

namespace App\Http\Requests\Customer;

use App\Models\Enum\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^((\+92)?(0092)?(92)?(0)?)(3)([0-9]{9})$/',
                'unique:customers,mobile_number',
            ],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'location_id' => ['nullable', 'integer', 'exists:operating_cities,id'],
            'dob' => ['nullable', 'date', 'before:today'],
            'image' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 03001234567 or +923001234567)',
            'mobile_number.unique' => 'This mobile number is already registered.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'dob.before' => 'Date of birth must be before today.',
        ];
    }
}
