<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        $business = $this->route('type') === 'business';

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(\+?265|0)[1-9]\d{8}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
            'industry' => [$business ? 'required' : 'nullable', 'string', 'max:60'],
            'business_type' => [$business ? 'required' : 'nullable', 'string', 'max:60'],
            'investor_type' => [$business ? 'nullable' : 'required', 'string', 'max:60'],
            'risk' => [$business ? 'nullable' : 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a Malawi mobile number, for example 0999 123 456.',
            'terms.accepted' => 'Accept the terms to continue.',
            'risk.accepted' => 'Confirm that you understand the investment risks.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/[\s-]/', '', (string) $this->input('phone')), 'email' => strtolower(trim((string) $this->input('email')))]);
    }
}
