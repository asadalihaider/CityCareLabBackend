<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\NormalizesPakistanMobile;
use App\Models\Enum\OtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOtpRequest extends FormRequest
{
    use NormalizesPakistanMobile;

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
                'regex:/^923[0-9]{9}$/',
                'exists:customers,mobile_number',
            ],
            'type' => ['required', Rule::enum(OtpType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 923001234567).',
            'mobile_number.exists' => 'Customer not found. Please register first.',
            'type.enum' => 'Invalid OTP type specified.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePakistanMobileField('mobile_number');
    }
}
