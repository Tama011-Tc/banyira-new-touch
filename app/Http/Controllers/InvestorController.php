<?php

namespace App\Http\Controllers;

use App\Models\LoanAgreement;
use App\Services\AuditLogger;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvestorController extends Controller
{
    public function profile(Request $request)
    {
        $p = $request->user()->investorProfile;

        return Inertia::render('investor/Profile', ['profile' => [
            'kyc_status' => $p->kyc_status, 'district' => $p->district, 'phone' => $p->phone, 'has_national_id' => (bool) $p->national_id,
            'risk_acknowledged_at' => $p->risk_acknowledged_at?->toDateString(),
            'payout_bank' => $p->payout_bank, 'payout_account_name' => $p->payout_account_name, 'payout_account_masked' => $p->maskedAccount(), 'payout_details_changed_at' => $p->payout_details_changed_at?->toDateString(),
        ], 'districts' => config('banyira.districts')]);
    }

    /** Identity details are submitted for review by the compliance team; the national ID is stored encrypted and never shown back. */
    public function submitKyc(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['national_id' => 'required|string|min:6|max:30|regex:/^[A-Za-z0-9\-]+$/', 'district' => 'required|string|max:60', 'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-]{9,20}$/']]);
        $p = $request->user()->investorProfile;
        if ($p->kyc_status === 'verified') {
            return back()->with('error', 'Your identity is already verified.');
        }
        $p->forceFill($d + ['kyc_status' => 'submitted'])->save();
        $audit->record('kyc.submitted', $p);

        return back()->with('success', 'Submitted. Our compliance team will review your details.');
    }

    /** Where repayments are paid. Changing it is how a fraudster would redirect an investor's money, so it needs the password, is audited, notifies the investor, and is flagged to finance for 3 days. */
    public function savePayout(Request $request, AuditLogger $audit)
    {
        $d = $request->validate(['payout_bank' => 'required|string|max:80', 'payout_account_name' => 'required|string|max:120', 'payout_account_number' => ['required', 'string', 'regex:/^[0-9]{6,20}$/'], 'current_password' => 'required|current_password']);
        $p = $request->user()->investorProfile;
        $p->forceFill(['payout_bank' => $d['payout_bank'], 'payout_account_name' => $d['payout_account_name'], 'payout_account_number' => $d['payout_account_number'], 'payout_details_changed_at' => now()])->save();
        $audit->record('investor.payout_details_changed', $p, ['bank' => $d['payout_bank'], 'account' => $p->maskedAccount()]);
        app(\App\Services\Sms\SmsService::class)->send($request->user(), 'security', config('banyira.brand').': your payout bank details were changed. If this was not you, contact support now.');

        return back()->with('success', 'Bank details saved.');
    }

    public function acknowledgeRisk(Request $request, AuditLogger $audit)
    {
        $request->validate(['accept' => 'accepted']);
        $request->user()->investorProfile->forceFill(['risk_acknowledged_at' => now()])->save();
        $audit->record('investor.risk_acknowledged', $request->user()->investorProfile);

        return back()->with('success', 'Thank you. You can now contact businesses.');
    }

    public function portfolio(Request $request, ReportingService $reports)
    {
        $ag = LoanAgreement::where('investor_id', $request->user()->id)->with(['business', 'listing'])->latest('id')->get();

        return Inertia::render('investor/Portfolio', [
            'summary' => $reports->investorPortfolio($request->user()) + ['paid_out' => (int) \App\Models\Payout::where('investor_id', $request->user()->id)->where('status', 'released')->sum('amount'), 'awaiting_payout' => (int) \App\Models\Payout::where('investor_id', $request->user()->id)->where('status', 'pending')->sum('amount')],
            'investments' => $ag->map(fn ($a) => ['id' => $a->id, 'business' => $a->business->display_name, 'principal' => $a->principal, 'rate_bp' => $a->rate_bp, 'term_months' => $a->term_months, 'status' => $a->status, 'risk' => $a->listing->risk_category, 'total_payable' => $a->total_payable]),
        ]);
    }

    public function statement(Request $request)
    {
        $rows = [];
        foreach (LoanAgreement::where('investor_id', $request->user()->id)->with('schedule', 'business')->get() as $a) {
            foreach ($a->schedule as $s) {
                $rows[] = ['AGR-'.$a->id, $a->business->display_name, $s->number, $s->due_on->toDateString(), $this->dec($s->principal_due), $this->dec($s->interest_due), $this->dec($s->principal_paid), $this->dec($s->interest_paid), $s->status, $a->simulated ? 'SIMULATED' : 'REAL'];
            }
        }

        return $this->csv('investor-statement-'.now()->format('Ymd').'.csv', ['Agreement', 'Business', 'Instalment', 'Due', 'Principal due', 'Interest due', 'Principal paid', 'Interest paid', 'Status', 'Money'], $rows);
    }
}
