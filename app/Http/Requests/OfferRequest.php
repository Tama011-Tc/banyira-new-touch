<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Shape of an investor offer. Business rules (ceiling, band limits, remaining amount) are enforced in MarketplaceService. */
class OfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'annual_rate' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'term_months' => ['required', 'integer', 'min:1', 'max:120'],
            'conditions' => ['nullable', 'string', 'max:500'],
            'accept_risk' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return ['accept_risk.accepted' => 'Confirm that you understand you could lose some or all of this amount.'];
    }
}
