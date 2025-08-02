<?php

namespace App\Http\Requests;

use App\Models\Enum\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => [
                'required',
                'string',
                'regex:/^(\+92|0)?3[0-9]{9}$/',
                'unique:customers,mobile_number',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'location' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 03001234567 or +923001234567)',
            'mobile_number.unique' => 'This mobile number is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'date_of_birth.before' => 'Date of birth must be before today.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile_number')) {
            $mobile = $this->input('mobile_number');

            $mobile = preg_replace('/[\s\-]/', '', $mobile);

            if (str_starts_with($mobile, '+92')) {
                $mobile = '0'.substr($mobile, 3);
            }

            $this->merge([
                'mobile_number' => $mobile,
            ]);
        }
    }
}
