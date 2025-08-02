<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerLoginRequest extends FormRequest
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
            'mobile_number' => [
                'required',
                'string',
                'regex:/^(\+92|0)?3[0-9]{9}$/',
            ],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mobile_number.regex' => 'Please enter a valid Pakistani mobile number (e.g., 03001234567 or +923001234567)',
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
