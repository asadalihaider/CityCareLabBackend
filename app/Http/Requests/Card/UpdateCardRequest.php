<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'card_id' => ['required', 'integer', 'exists:physical_cards,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_id.required' => 'Card ID is required.',
            'card_id.integer' => 'Card ID must be a valid integer.',
            'card_id.exists' => 'The specified card does not exist.',
            'is_active.required' => 'Active status is required.',
            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }
}
