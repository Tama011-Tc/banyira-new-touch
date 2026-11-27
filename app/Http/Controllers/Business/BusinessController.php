<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\InvestmentOffer;
use App\Models\LoanApplication;
use App\Models\RepaymentSchedule;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function overview(Request $request, PaymentService $payments)
    {
        $user = $request->user()->load('businessProfile');
        $profile = $user->businessProfile;
        $latest = $profile->applications()->with('product:id,name', 'listing:id,loan_application_id,amount_requested,amount_accepted,status')->latest('id')->first();
        $agreements = Agreement::where('borrower_id', $user->id)->get();
        $active = $agreements->where('status', 'active');

        return Inertia::render('Business/Overview', [
            'profile' => ['name' => $profile->displayName(), 'verification' => $profile->verification_status],
            'emailVerified' => (bool) $user->email_verified_at,
            'application' => $latest ? [
                'id' => $latest->id, 'reference' => $latest->reference, 'product' => $latest->product->name, 'amount' => $latest->amount,
                'status' => $latest->status, 'statusLabel' => $latest->statusLabel(), 'risk' => $latest->risk_class,
                'listing' => $latest->listing?->only(['amount_requested', 'amount_accepted', 'status']),
            ] : null,
            'openRequests' => $profile->applications()->whereHas('informationRequests', fn ($q) => $q->where('status', 'open'))->count(),
            'stats' => [
                'active' => $active->count(),
                'financed' => Money::add('0', ...$agreements->whereIn('status', ['active', 'completed'])->pluck('principal_total')->all()),
                'outstanding' => Money::add('0', ...$active->map(fn ($a) => $payments->outstanding($a))->all()),
                'pendingOffers' => InvestmentOffer::where('status', 'pending')->whereHas('listing.application', fn ($q) => $q->where('business_profile_id', $profile->id))->count(),
            ],
            'upcoming' => $this->upcoming($active->pluck('id')->all(), 3),
            'activity' => InvestmentOffer::whereHas('listing.application', fn ($q) => $q->where('business_profile_id', $profile->id))
                ->latest('updated_at')->limit(5)->get(['reference', 'amount', 'annual_rate', 'term_months', 'status', 'updated_at']),
            'notifications' => $user->notifications()->latest()->limit(4)->get()->map(fn ($n) => ['title' => $n->data['title'] ?? '', 'body' => $n->data['body'] ?? '', 'at' => $n->created_at, 'read' => (bool) $n->read_at]),
        ]);
    }

    /** Instalments grouped by due date across all of a borrower's tranches. */
    public static function upcoming(array $agreementIds, int $limit = 12): array
    {
        return RepaymentSchedule::whereIn('agreement_id', $agreementIds)->where('status', '!=', 'paid')
            ->selectRaw('due_date, SUM(principal_due - principal_paid) principal, SUM(interest_due - interest_paid) interest, MAX(CASE WHEN status = \'overdue\' THEN 1 ELSE 0 END) overdue')
            ->groupBy('due_date')->orderBy('due_date')->limit($limit)->get()
            ->map(fn ($r) => ['due' => $r->due_date->toDateString(), 'principal' => Money::of($r->principal), 'interest' => Money::of($r->interest), 'total' => Money::add($r->principal, $r->interest), 'overdue' => (bool) $r->overdue])
            ->all();
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('businessProfile', 'verifications');

        return Inertia::render('Business/Profile', [
            'profile' => $user->businessProfile->only(['legal_name', 'trading_name', 'registration_number', 'business_type', 'industry', 'region', 'district', 'address', 'years_operating', 'employees', 'monthly_turnover', 'description', 'payout_method', 'verification_status']),
            'contact' => $user->only(['name', 'email', 'phone']),
            'verifications' => $user->verifications->map->only(['kind', 'status', 'reviewed_at']),
            'industries' => CustomerAuthController::INDUSTRIES,
            'regions' => CustomerAuthController::REGIONS,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'trading_name' => ['nullable', 'string', 'max:120'],
            'region' => ['required', 'in:'.implode(',', CustomerAuthController::REGIONS)],
            'district' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:200'],
            'employees' => ['required', 'integer', 'min:0'],
            'monthly_turnover' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:1000'],
        ]);
        $request->user()->businessProfile->update($data);

        return back()->with('success', 'Business profile saved.');
    }
}
