<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\Concerns\NormalizesPakistanMobile;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use NormalizesPakistanMobile;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $isValidEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isValidPhone = preg_match('/^923[0-9]{9}$/', $value);

                    if (! $isValidEmail && ! $isValidPhone) {
                        $fail('Please enter a valid email address or Pakistani mobile number (e.g., user@example.com, 03001234567, or 923001234567).');
                    }
                },
            ],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Please enter your mobile number or email address',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePakistanMobileFieldWhenNotEmail('login');
    }
}
