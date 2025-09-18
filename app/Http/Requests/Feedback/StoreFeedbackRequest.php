<?php

namespace App\Http\Requests\Feedback;

use App\Models\Enum\FeedbackCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'category' => ['required', 'string', Rule::in(FeedbackCategory::values())],
            'is_anonymous' => ['boolean'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Please provide a subject for your feedback.',
            'subject.max' => 'Subject must not exceed 255 characters.',
            'message.required' => 'Please provide your feedback message.',
            'message.max' => 'Message must not exceed 2000 characters.',
            'rating.integer' => 'Rating must be a number.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating must not exceed 5.',
            'category.required' => 'Please select a feedback category.',
            'category.in' => 'Invalid feedback category selected.',
            'contact_email.email' => 'Please provide a valid email address.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'feedback subject',
            'message' => 'feedback message',
            'rating' => 'rating',
            'category' => 'category',
            'contact_email' => 'contact email',
        ];
    }
}
