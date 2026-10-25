<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Business;
use App\Models\Disbursement;
use App\Models\InvestorProfile;
use App\Models\Listing;
use App\Models\LoanAgreement;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\ListingService;
use App\Services\MessagingService;
use App\Services\OfferService;
use App\Services\PaymentService;
use App\Services\SandboxProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Most tests assume a flat 2% borrower / 1% investor / 1.5% provider schedule is in force. Set false to test the no-fee default. */
    protected bool $withFees = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        if ($this->withFees) {
            \App\Models\FeeSchedule::create(['name' => 'Test schedule', 'effective_from' => now()->subDay()->toDateString(), 'tax_bp' => 0, 'processing_bp' => 150,
                'tiers' => [['min' => 0, 'max' => null, 'origination_bp' => 200, 'origination_fixed' => 0, 'investor_bp' => 100, 'investor_fixed' => 0]]]);
        }
    }

    protected function user(Role $role = Role::Investor, array $attrs = []): User
    {
        $u = User::factory()->role($role)->create($attrs)->fresh();
        if ($role === Role::Investor) {
            $u->investorProfile->update(['kyc_status' => 'verified', 'risk_acknowledged_at' => now(), 'national_id' => 'TEST-123456', 'payout_bank' => 'Test Bank', 'payout_account_name' => $u->name, 'payout_account_number' => '1002003004']);
        }

        return $u->fresh();
    }

    protected function business(?User $owner = null, array $attrs = []): Business
    {
        $owner ??= $this->user(Role::BusinessOwner);

        return Business::create($attrs + ['owner_id' => $owner->id, 'name' => 'Test Trading Ltd', 'display_name' => 'Test Trading', 'sector' => 'Services', 'district' => 'Lilongwe', 'description' => 'A fictional test business that sells services to local firms.', 'years_operating' => 5, 'owner_consent_at' => now(), 'verification_status' => 'verified']);
    }

    protected function application(Business $b, array $attrs = [], bool $docs = true): Application
    {
        $a = Application::create($attrs + ['business_id' => $b->id, 'status' => 'draft', 'amount' => 600000000, 'purpose' => 'Buy equipment to grow monthly output and revenue for the business.', 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'proposed_rate_bp' => 2400, 'monthly_revenue' => 400000000, 'monthly_expenses' => 250000000, 'existing_debt' => 0, 'records_months' => 12]);
        if ($docs) {
            foreach (config('banyira.documents.required') as $t) {
                ApplicationDocument::create(['application_id' => $a->id, 'uploaded_by' => $b->owner_id, 'type' => $t, 'original_name' => "$t.pdf", 'path' => "documents/{$a->id}/$t.pdf", 'mime' => 'application/pdf', 'size' => 10, 'sha256' => hash('sha256', $t)]);
            }
        }

        return $a;
    }

    /** Run the whole credit pipeline and publish. Returns the live Listing. */
    protected function listing(array $appAttrs = []): Listing
    {
        $b = $this->business();
        $a = $this->application($b, $appAttrs);
        $wf = app(ApplicationWorkflow::class);
        $analyst = $this->user(Role::CreditAnalyst);
        $approver = $this->user(Role::CreditApprover);
        $exec = $this->user(Role::Executive);
        $wf->submit($a, $b->owner);
        $credit = app(CreditService::class);
        $credit->startScreening($a->fresh(), $analyst);
        $credit->beginAssessment($a->fresh(), $analyst);
        $credit->assess($a->fresh(), $analyst, $credit->suggest($a->fresh())['score'], 'Reviewed documents and cash flow; consistent.', 'approve');
        $credit->decide($a->fresh(), $approver, 'approved', 'Meets policy on cover and documentation.', null, null, 3000);
        $l = app(ListingService::class);
        $listing = $l->prepare($a->fresh(), $approver, ['title' => 'Test listing', 'summary' => str_repeat('A fictional business expanding. ', 3), 'risk_disclosure' => str_repeat('Repayment may be late or missed; capital can be lost. ', 2), 'min_investment' => 10000000, 'max_investment' => $a->amount, 'deadline' => now()->addDays(20)->toDateString()]);
        $l->review($listing, $exec);

        return $l->publish($listing->fresh(), $exec);
    }

    protected function agreement(?Listing $listing = null, ?User $investor = null, int $principal = 100000000, int $rateBp = 2400): LoanAgreement
    {
        $listing ??= $this->listing();
        $investor ??= $this->user(Role::Investor);
        $owner = $listing->application->business->owner;
        $c = app(MessagingService::class)->start($listing, $investor);
        $offers = app(OfferService::class);
        $v = $offers->propose($c, $investor, ['principal' => $principal, 'rate_bp' => $rateBp, 'term_months' => 12, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);

        return $offers->accept($v, $owner);
    }

    protected function fund(LoanAgreement $a): \App\Models\PaymentAttempt
    {
        $pay = app(PaymentService::class);
        $attempt = $pay->initiate($a->investor, $a, 'funding', $pay->fundingAmount($a), (string) Str::uuid());
        $hook = app(SandboxProvider::class)->settle($attempt->provider_ref);
        $pay->handleWebhook($hook['body'], $hook['signature']);

        return $attempt->fresh();
    }

    protected function disburse(LoanAgreement $a): void
    {
        app(PaymentService::class)->approveDisbursement(Disbursement::where('agreement_id', $a->id)->firstOrFail(), $this->user(Role::Finance));
    }

    protected function activeLoan(int $principal = 100000000): LoanAgreement
    {
        $a = $this->agreement($this->listing(['amount' => $principal]), null, $principal);   // fully funds the campaign
        $this->fund($a);
        $this->disburse($a);

        return $a->fresh();
    }

    protected function repay(LoanAgreement $a, int $amount): \App\Models\PaymentAttempt
    {
        $pay = app(PaymentService::class);
        $owner = $a->business->owner;
        $attempt = $pay->initiate($owner, $a, 'repayment', $amount, (string) Str::uuid());
        $hook = app(SandboxProvider::class)->settle($attempt->provider_ref);
        $pay->handleWebhook($hook['body'], $hook['signature']);

        return $attempt->fresh();
    }
}
