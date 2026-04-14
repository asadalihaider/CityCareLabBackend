<?php

namespace App\Http\Requests\Outbox;

use App\Http\Requests\Concerns\NormalizesPakistanMobile;
use App\Models\Enum\OutboxChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    use NormalizesPakistanMobile;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validChannels = array_merge(['auto'], OutboxChannel::values());

        return [
            'mobile' => [
                'required',
                'string',
                'regex:/^923[0-9]{9}$/',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'body' => ['required', 'string'],
            'channel' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in($validChannels),
            ],
            'data' => ['sometimes', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a valid Pakistani mobile number (e.g. 923001234567).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePakistanMobileField('mobile');

        if ($this->has('channel') && is_string($this->channel)) {
            $this->merge([
                'channel' => strtolower(trim($this->channel)),
            ]);
        }
    }
}
