<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class SetExpoPushTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'customer_id' => ['sometimes', 'exists:customers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Expo push token is required.',
            'token.string' => 'Expo push token must be a string.',
            'customer_id.exists' => 'Customer not found.',
        ];
    }
}
