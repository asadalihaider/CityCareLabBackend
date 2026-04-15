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
            'mobile' => ['required', 'string', 'regex:/^923[0-9]{9}$/'],
            'channel' => ['sometimes', 'nullable', 'string', Rule::in($validChannels)],
            'data' => ['required', 'array'],
            'data.title' => ['required', 'string', 'max:255'],
            'data.body' => ['required', 'string'],
            'data.event' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a valid Pakistani mobile number (e.g. 923001234567).',
            'channel.in' => 'The selected channel is invalid. Allowed values are auto, expo, whatsapp, and sms.',
            'data.required' => 'The data object is required.',
            'data.array' => 'The data field must be a valid JSON object.',
            'data.title.required' => 'The title is required in data object.',
            'data.title.string' => 'The title must be a string.',
            'data.title.max' => 'The title may not be greater than 255 characters.',
            'data.body.required' => 'The body is required in data object.',
            'data.body.string' => 'The body must be a string.',
            'data.event.string' => 'The event must be a string.',
            'data.event.max' => 'The event may not be greater than 255 characters.',
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
