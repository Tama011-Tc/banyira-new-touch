<?php

namespace App\Http\Controllers;

use App\Services\FeeScheduleService;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Public marketing pages. Every figure shown comes from live settings, so the site never promises terms the platform does not enforce. */
class SiteController extends Controller
{
    public function home(FeeScheduleService $fees): Response
    {
        return Inertia::render('Welcome', ['terms' => $this->terms(), 'fee_tiers' => $this->feeTiers($fees)]);
    }

    public function products(FeeScheduleService $fees): Response
    {
        return Inertia::render('site/Products', ['terms' => $this->terms(), 'fee_tiers' => $this->feeTiers($fees)]);
    }

    public function loanGuide(): Response
    {
        return Inertia::render('site/LoanGuide', ['terms' => $this->terms()]);
    }

    public function support(FeeScheduleService $fees): Response
    {
        return Inertia::render('site/Support', ['terms' => $this->terms(), 'fee_tiers' => $this->feeTiers($fees), 'support_email' => config('banyira.support_email')]);
    }

    public function about(): Response
    {
        return Inertia::render('site/About', ['terms' => $this->terms()]);
    }

    public function investors(): Response
    {
        return Inertia::render('site/Investors', ['terms' => $this->terms()]);
    }

    /** English or Chichewa for the public site; remembered on the account when signed in. */
    public function language(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => 'required|in:en,ny'])['locale'];
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back()->withCookie(cookie()->forever('site_locale', $locale));
    }

    /** The business fee by loan size (investors pay none). */
    private function feeTiers(FeeScheduleService $fees): \Illuminate\Support\Collection
    {
        return collect($fees->active()?->tiers ?? [])->map(fn ($t) => ['min' => $t['min'], 'max' => $t['max'], 'bp' => $t['origination_bp']])->values();
    }

    /** @return array<string, mixed> */
    private function terms(): array
    {
        return [
            'max_rate_bp' => (int) Settings::get('loan.max_rate_bp'),
            'max_term_months' => (int) Settings::get('loan.max_term_months'),
            'max_grace' => (int) Settings::get('loan.max_grace_instalments'),
            'min_trading_years' => (int) Settings::get('eligibility.min_trading_years'),
            'min_records_months' => (int) Settings::get('eligibility.min_records_months'),
            'min_commitment' => (int) Settings::get('investor.min_commitment'),
            'sms_max_accept' => (int) Settings::get('sms.max_accept_amount'),
            'support_phone' => config('banyira.support_phone'),
            'support_hours' => config('banyira.support_hours'),
            'bands' => collect(Settings::get('credit.bands'))->map(fn ($b) => ['code' => $b['code'], 'name' => $b['name'], 'definition' => $b['definition'], 'max_amount' => $b['max_amount'] ?? null, 'max_term_months' => $b['max_term_months'] ?? null])->values(),
        ];
    }
}
