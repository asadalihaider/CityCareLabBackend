<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\NormalizesPakistanMobile;
use App\Models\Enum\OtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
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
            ],
            'otp' => ['required', 'string', 'size:6'],
            'type' => ['required', Rule::enum(OtpType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 03001234567 or +923001234567).',
            'otp.size' => 'OTP must be exactly 6 digits.',
            'type.enum' => 'Invalid OTP type specified.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePakistanMobileField('mobile_number');
    }
}
