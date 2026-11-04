<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Saving a draft application. Completeness is checked on submit, not here. */
class ApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'loan_product_id' => ['required', 'exists:loan_products,id'],
            'amount' => ['nullable', 'numeric', 'min:1', 'max:9999999999999'],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'monthly_turnover' => ['nullable', 'numeric', 'min:0'],
            'employees' => ['nullable', 'integer', 'min:0'],
            'main_customers' => ['nullable', 'string', 'max:60'],
            'records' => ['nullable', 'array', 'max:12'],
            'records.*.period' => ['required', 'date_format:Y-m'],
            'records.*.inflow' => ['required', 'numeric', 'min:0'],
            'records.*.outflow' => ['required', 'numeric', 'min:0'],
        ];
    }
}
