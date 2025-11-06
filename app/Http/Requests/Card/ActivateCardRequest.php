<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class ActivateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'min:8', 'max:50'],
            'expiry_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'Card serial number is required',
            'identifier.string' => 'Card serial number must be a string',
            'identifier.min' => 'Card serial number must be at least 8 characters',
            'identifier.max' => 'Card serial number cannot exceed 50 characters',
            'expiry_date.required' => 'Card expiry date is required',
            'expiry_date.date' => 'Card expiry date must be a valid date',
        ];
    }

    public function attributes(): array
    {
        return [
            'identifier' => 'card serial number',
            'expiry_date' => 'card expiry date',
        ];
    }
}
