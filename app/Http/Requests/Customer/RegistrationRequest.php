<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\NormalizesPakistanMobile;
use Illuminate\Foundation\Http\FormRequest;

class RegistrationRequest extends FormRequest
{
    use NormalizesPakistanMobile;

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
                'regex:/^923[0-9]{9}$/',
                'unique:customers,mobile_number',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 923001234567).',
            'mobile_number.unique' => 'This mobile number is already registered.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePakistanMobileField('mobile_number');
    }
}
