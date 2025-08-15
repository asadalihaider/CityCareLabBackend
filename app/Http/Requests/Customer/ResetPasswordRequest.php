<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'string',
                'regex:/^((\+92)?(0092)?(92)?(0)?)(3)([0-9]{9})$/',
                'exists:customers,mobile_number',
            ],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 03001234567 or +923001234567)',
            'mobile_number.exists' => 'No account found with this mobile number.',
            'otp.size' => 'OTP must be exactly 6 digits',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
