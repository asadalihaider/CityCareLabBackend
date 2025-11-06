<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'card_id' => ['required', 'integer', 'exists:discount_cards,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_id.required' => 'Card ID is required',
            'card_id.integer' => 'Card ID must be a valid number',
            'card_id.exists' => 'Selected card does not exist',
        ];
    }

    public function attributes(): array
    {
        return [
            'card_id' => 'card ID',
        ];
    }
}
