<?php

namespace App\Http\Requests\Outbox;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validEvents = array_keys(config('outbox.templates', []));

        return [
            'mobile' => [
                'required',
                'string',
                'regex:/^(?:\+92|0)3[0-9]{9}$/',
            ],
            'event' => [
                'required',
                'string',
                Rule::in($validEvents),
            ],
            'data' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        $validEvents = implode(', ', array_keys(config('outbox.templates', [])));

        return [
            'mobile.regex' => 'Please enter a valid Pakistani mobile number (e.g. 923001234567).',
            'event.in' => "The event must be one of: {$validEvents}.",
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile')) {
            $this->merge([
                'mobile' => preg_replace('/\D/', '', $this->mobile),
            ]);
        }
    }
}
