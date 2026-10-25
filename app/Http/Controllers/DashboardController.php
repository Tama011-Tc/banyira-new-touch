<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\LoanAgreement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /** Each role lands in its own workspace. */
    public function __invoke(Request $request)
    {
        $u = $request->user();

        return match ($u->role->value) {
            'investor' => to_route('marketplace.index'),
            'sales' => to_route('sales.index'),
            'credit_analyst', 'credit_approver' => to_route('credit.index'),
            'executive' => to_route('executive.index'),
            'finance' => to_route('finance.index'),
            'compliance' => to_route('compliance.index'),
            'it_admin' => to_route('admin.users'),
            'support' => to_route('moderation.index'),
            default => $this->business($u),
        };
    }

    private function business($u)
    {
        $b = $u->business;
        $apps = $b ? Application::where('business_id', $b->id)->latest('id')->get() : collect();

        return Inertia::render('business/Overview', [
            'business' => $b ? ['id' => $b->id, 'display_name' => $b->display_name, 'verification_status' => $b->verification_status] : null,
            'applications' => $apps->map(fn ($a) => ['id' => $a->id, 'status' => $a->status, 'amount' => $a->amount, 'purpose' => $a->purpose, 'info_request' => $a->info_request]),
            'loans' => $b ? LoanAgreement::where('business_id', $b->id)->where('status', '!=', 'cancelled')->count() : 0,
        ]);
    }
}
