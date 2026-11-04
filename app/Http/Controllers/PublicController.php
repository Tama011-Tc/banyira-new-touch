<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LoanProduct;
use App\Services\RulesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function __construct(private RulesService $rules) {}

    public function home(): Response
    {
        return Inertia::render('Public/Home', ['products' => LoanProduct::active()->get(['slug', 'code', 'name', 'summary', 'min_amount', 'max_amount', 'min_months', 'max_months']), 'rules' => $this->rulesSummary()]);
    }

    public function products(): Response
    {
        return Inertia::render('Public/Products', ['products' => LoanProduct::active()->get(), 'rules' => $this->rulesSummary()]);
    }

    public function product(LoanProduct $product): Response
    {
        abort_unless($product->is_active, 404);

        return Inertia::render('Public/Product', [
            'product' => $product,
            'others' => LoanProduct::active()->whereKeyNot($product->id)->get(['slug', 'name', 'summary']),
            'rules' => $this->rulesSummary(),
        ]);
    }

    public function loan(): Response
    {
        return Inertia::render('Public/HowToGetALoan', ['rules' => $this->rulesSummary()]);
    }

    public function invest(): Response
    {
        return Inertia::render('Public/HowToInvest', ['rules' => $this->rulesSummary(), 'minOffer' => config('banyira.marketplace.min_offer')]);
    }

    public function page(string $page): Response
    {
        return Inertia::render('Public/'.$page);
    }

    public function contact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:business,investor,other'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        Lead::create([...$data, 'source' => 'Website contact form', 'stage' => 'new']);

        return back()->with('success', 'Thank you. We will reply within two working days.');
    }

    private function rulesSummary(): array
    {
        $r = $this->rules->active();

        return [
            'version' => $r->version,
            'effective' => $r->effective_from->toDateString(),
            'ceiling' => $r->interest_ceiling,
            'fees' => $r->feeTiers->map(fn ($t) => ['upTo' => $t->upper_bound, 'rate' => $t->rate])->values(),
            'bands' => $r->riskBands->sortBy('min_score', descending: true)->map(fn ($b) => ['class' => $b->risk_class, 'label' => $b->label, 'maxAmount' => $b->max_amount, 'maxMonths' => $b->max_months])->values(),
        ];
    }
}
