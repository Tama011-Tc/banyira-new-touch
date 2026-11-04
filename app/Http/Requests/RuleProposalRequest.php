<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RuleProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('rules.propose');
    }

    public function rules(): array
    {
        return [
            'effective_from' => ['required', 'date', 'after_or_equal:today'],
            'interest_ceiling' => ['required', 'numeric', 'min:1', 'max:100'],
            'notes' => ['required', 'string', 'max:300'],
            'fee_tiers' => ['required', 'array', 'min:1', 'max:6'],
            'fee_tiers.*.upper_bound' => ['nullable', 'numeric', 'min:1'],
            'fee_tiers.*.rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'bands' => ['required', 'array', 'size:4'],
            'bands.*.risk_class' => ['required', 'in:A+,A,B,C', 'distinct'],
            'bands.*.label' => ['required', 'string', 'max:40'],
            'bands.*.min_score' => ['required', 'integer', 'between:0,100'],
            'bands.*.max_amount' => ['nullable', 'numeric', 'min:1'],
            'bands.*.max_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [function ($v) {
            $tiers = $this->input('fee_tiers', []);
            $bounds = array_column($tiers, 'upper_bound');
            if (end($bounds) !== null && end($bounds) !== '') {
                $v->errors()->add('fee_tiers', 'The last fee tier must have no upper limit.');
            }
            $set = array_filter(array_slice($bounds, 0, -1), fn ($b) => $b !== null && $b !== '');
            $sorted = $set;
            sort($sorted, SORT_NUMERIC);
            if (count($set) !== count($bounds) - 1 || $set !== $sorted) {
                $v->errors()->add('fee_tiers', 'Each fee tier except the last needs an upper limit, in increasing order.');
            }
        }];
    }
}
