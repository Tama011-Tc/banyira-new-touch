<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\RuleProposalRequest;
use App\Models\LoanProduct;
use App\Models\RuleSet;
use App\Services\AuditLogger;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

/** Financing rules (versioned, approved by someone else) and product configuration. */
class ConfigController extends Controller
{
    public function rules(Request $request, RulesService $rules)
    {
        return Inertia::render('Staff/Rules', [
            'active' => $this->present($rules->active()),
            'history' => RuleSet::with('feeTiers', 'riskBands', 'proposer:id,name', 'decider:id,name')->latest('id')->get()->map(fn ($r) => $this->present($r)),
            'canPropose' => $request->user()->hasPermission('rules.propose'),
            'pending' => RuleSet::where('status', 'pending_approval')->exists(),
        ]);
    }

    public function propose(RuleProposalRequest $request, RulesService $rules)
    {
        $set = $rules->propose($request->validated(), $request->user());

        return back()->with('success', "Proposal {$set->version} sent to Executive Management for approval.");
    }

    public function products()
    {
        return Inertia::render('Staff/Products', ['products' => LoanProduct::orderBy('sort_order')->get()]);
    }

    public function updateProduct(Request $request, LoanProduct $product, AuditLogger $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'summary' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:2000'],
            'audience' => ['required', 'string', 'max:500'],
            'repayment_info' => ['required', 'string', 'max:500'],
            'purposes' => ['required', 'array', 'min:1'], 'purposes.*' => ['required', 'string', 'max:120'],
            'eligibility' => ['required', 'array', 'min:1'], 'eligibility.*' => ['required', 'string', 'max:160'],
            'requirements' => ['required', 'array', 'min:1'], 'requirements.*' => ['required', 'string', 'max:160'],
            'min_amount' => ['required', 'numeric', 'min:1'],
            'max_amount' => ['required', 'numeric', 'gt:min_amount'],
            'min_months' => ['required', 'integer', 'min:1'],
            'max_months' => ['required', 'integer', 'gte:min_months', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ]);
        $before = $product->only(array_keys($data));
        $product->update($data);
        Cache::forget('nav-products');
        $audit->log('products', "Updated product {$product->name}", $product, ['before' => $before], $request->user());

        return back()->with('success', 'Product saved. The public website now shows the change.');
    }

    private function present(RuleSet $r): array
    {
        $r->loadMissing('feeTiers', 'riskBands');

        return [
            ...$r->only(['id', 'version', 'effective_from', 'status', 'interest_ceiling', 'fee_basis', 'fee_collection', 'notes', 'decided_at', 'created_at']),
            'by' => $r->proposer?->name, 'decidedBy' => $r->decider?->name,
            'fees' => $r->feeTiers->map->only(['upper_bound', 'rate']),
            'bands' => $r->riskBands->sortByDesc('min_score')->values()->map->only(['risk_class', 'label', 'min_score', 'max_amount', 'max_months']),
        ];
    }
}
