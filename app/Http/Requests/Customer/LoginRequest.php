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
            'login' => ['required', 'string'],
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
