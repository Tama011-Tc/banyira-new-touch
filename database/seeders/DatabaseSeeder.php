<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Business;
use App\Models\Complaint;
use App\Models\Disbursement;
use App\Models\InvestorProfile;
use App\Models\Listing;
use App\Models\LoanAgreement;
use App\Models\RepaymentSchedule;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\CreditService;
use App\Services\ListingService;
use App\Services\MessagingService;
use App\Services\OfferService;
use App\Services\PaymentService;
use App\Services\RepaymentService;
use App\Services\SandboxProvider;
use App\Support\ComplianceChecklist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data. EVERYTHING here is fictional and simulated: the businesses, investors, documents, loans and "payments"
 * do not exist, and no money has moved. All accounts use the password "password".
 */
class DatabaseSeeder extends Seeder
{
    private array $u = [];

    public function run(): void
    {
        ComplianceChecklist::seed();
        $this->staffAndCustomers();
        $this->pipeline();
    }

    private function user(string $key, string $name, Role $role): User
    {
        $u = new User(['name' => $name, 'email' => "{$key}@banyira.test", 'password' => 'password']);
        $u->forceFill(['role' => $role, 'status' => 'active', 'email_verified_at' => now()])->save();
        if ($role === Role::Investor) {
            InvestorProfile::create(['user_id' => $u->id, 'kyc_status' => 'verified', 'national_id' => 'DEMO-'.strtoupper(Str::random(6)), 'district' => 'Lilongwe', 'phone' => '+265 999 000 000', 'risk_acknowledged_at' => now(), 'verified_at' => now()]);
        }

        return $this->u[$key] = $u;
    }

    private function staffAndCustomers(): void
    {
        foreach (['sales' => ['Sales Rep (demo)', Role::Sales], 'analyst' => ['Credit Analyst (demo)', Role::CreditAnalyst], 'approver' => ['Credit Approver (demo)', Role::CreditApprover], 'approver2' => ['Second Approver (demo)', Role::CreditApprover],
            'executive' => ['Executive (demo)', Role::Executive], 'finance' => ['Finance Officer (demo)', Role::Finance], 'finance2' => ['Finance Officer 2 (demo)', Role::Finance], 'compliance' => ['Compliance Officer (demo)', Role::Compliance],
            'admin' => ['IT Administrator (demo)', Role::ItAdmin], 'support' => ['Support & Moderation (demo)', Role::Support]] as $k => [$n, $r]) {
            $this->user($k, $n, $r);
        }
        $this->user('investor', 'Investor One (demo)', Role::Investor);
        $this->user('investor2', 'Investor Two (demo)', Role::Investor);
        $u = $this->user('investor3', 'Investor Three (unverified demo)', Role::Investor);
        $u->investorProfile->update(['kyc_status' => 'pending', 'risk_acknowledged_at' => null, 'verified_at' => null]);
        $this->user('business', 'Business Owner (demo)', Role::BusinessOwner);
        Business::create(['owner_id' => $this->u['business']->id, 'name' => 'Lilongwe Print Studio Ltd (demo)', 'display_name' => 'Lilongwe Print Studio', 'sector' => 'Services', 'district' => 'Lilongwe', 'description' => 'A small digital print and signage shop serving schools and local firms. Fictional demo business.', 'years_operating' => 4, 'registration_no' => 'DEMO-REG-0001', 'owner_consent_at' => now(), 'verification_status' => 'verified']);
    }

    private function business(string $key, string $name, string $display, string $sector, string $district, int $years, string $desc, ?User $assistedBy = null): Business
    {
        $owner = $this->user($key, "{$display} owner (demo)", Role::BusinessOwner);

        return Business::create(['owner_id' => $owner->id, 'name' => $name, 'display_name' => $display, 'sector' => $sector, 'district' => $district, 'description' => $desc.' (Fictional demo business.)', 'years_operating' => $years, 'registration_no' => 'DEMO-'.strtoupper(Str::random(6)), 'registered_by_staff_id' => $assistedBy?->id, 'owner_consent_at' => now(), 'verification_status' => 'verified']);
    }

    private function application(Business $b, array $o): Application
    {
        $a = Application::create($o + ['business_id' => $b->id, 'status' => 'draft', 'frequency' => 'monthly', 'interest_method' => 'reducing', 'existing_debt' => 0]);
        foreach (['business_registration', 'owner_id', 'bank_statements'] as $t) {
            $path = "documents/{$a->id}/demo-{$t}.txt";
            Storage::disk('local')->put($path, "DEMO DOCUMENT ({$t}). Fictional; not a real record.");
            ApplicationDocument::create(['application_id' => $a->id, 'uploaded_by' => $b->owner_id, 'type' => $t, 'original_name' => "demo-{$t}.txt", 'path' => $path, 'mime' => 'text/plain', 'size' => 60, 'sha256' => hash('sha256', $t)]);
        }

        return $a;
    }

    private function pipeline(): void
    {
        $wf = app(ApplicationWorkflow::class);
        $credit = app(CreditService::class);
        $listings = app(ListingService::class);
        $mk = fn (int $k) => $k * 100;

        // Awaiting screening, registered with the help of the sales rep.
        $kasungu = $this->business('b_kasungu', 'Kasungu Haulage Ltd (demo)', 'Kasungu Haulage', 'Transport & logistics', 'Kasungu', 6, 'Light-truck haulage of produce to Lilongwe markets.', $this->u['sales']);
        $a = $this->application($kasungu, ['amount' => $mk(6000000), 'purpose' => 'Buy a second-hand light truck to double weekly produce deliveries to Lilongwe.', 'term_months' => 18, 'proposed_rate_bp' => 2400, 'monthly_revenue' => $mk(3200000), 'monthly_expenses' => $mk(2300000), 'existing_debt' => $mk(1500000), 'assisted_by' => $this->u['sales']->id, 'referred_by' => $this->u['sales']->id]);
        Auth::setUser($this->u['sales']);
        $wf->submit($a, $this->u['sales']);

        // In credit assessment with a recorded assessment, waiting for an approver.
        $dedza = $this->business('b_dedza', 'Dedza Dairy Cooperative (demo)', 'Dedza Dairy Co-op', 'Agriculture', 'Dedza', 8, 'Milk collection and chilling for smallholder farmers.');
        $a = $this->application($dedza, ['amount' => $mk(9000000), 'purpose' => 'Install a solar-powered milk chiller to cut spoilage and add forty supplying farmers.', 'term_months' => 24, 'proposed_rate_bp' => 2200, 'monthly_revenue' => $mk(4800000), 'monthly_expenses' => $mk(3300000), 'existing_debt' => $mk(1000000)]);
        Auth::setUser($this->u['dedza'] ?? $dedza->owner);
        $wf->submit($a, $dedza->owner);
        $this->assess($a, 'Cash flow supports the repayment comfortably. Co-op audited statements provided for two years.', 'approve');

        // Approved, awaiting listing preparation.
        $solar = $this->business('b_salima', 'Salima Solar Kits (demo)', 'Salima Solar Kits', 'Retail & trade', 'Salima', 5, 'Sells and installs home solar kits.');
        $a = $this->application($solar, ['amount' => $mk(7000000), 'purpose' => 'Buy inventory of home solar kits ahead of the dry season when demand peaks.', 'term_months' => 12, 'proposed_rate_bp' => 2400, 'monthly_revenue' => $mk(4200000), 'monthly_expenses' => $mk(2800000), 'existing_debt' => $mk(500000)]);
        Auth::setUser($solar->owner);
        $wf->submit($a, $solar->owner);
        $this->assess($a, 'Seasonal but strong turnover; supplier invoices reviewed.', 'approve');
        $this->decide($a);

        // Published listings and live loans.
        $chikondi = $this->business('b_chikondi', 'Chikondi Poultry Farm (demo)', 'Chikondi Poultry', 'Agriculture', 'Mzuzu', 7, 'Broiler and layer production supplying hotels and supermarkets in Mzuzu.');
        $open = $this->published($chikondi, ['amount' => $mk(12000000), 'purpose' => 'Build a second poultry house for 2,000 additional layers.', 'term_months' => 24, 'proposed_rate_bp' => 2200, 'monthly_revenue' => $mk(5200000), 'monthly_expenses' => $mk(3600000), 'existing_debt' => $mk(800000)], 'Expand egg production: second layer house');

        $zomba = $this->business('b_zomba', 'Zomba Fresh Foods (demo)', 'Zomba Fresh Foods', 'Food & hospitality', 'Zomba', 5, 'Prepared-meal supplier to schools and offices in Zomba.');
        $zl = $this->published($zomba, ['amount' => $mk(5000000), 'purpose' => 'Commercial kitchen equipment to serve three more schools.', 'term_months' => 12, 'proposed_rate_bp' => 2000, 'monthly_revenue' => $mk(2900000), 'monthly_expenses' => $mk(1900000), 'existing_debt' => 0], 'Kitchen equipment for school meal contracts');
        $ag = $this->negotiateAndFund($zl, $this->u['investor2'], $zomba->owner, $mk(5000000), 2000);
        $this->repay($ag, 2);

        $mzuzu = $this->business('b_mzuzu', 'Mzuzu Tailors Collective (demo)', 'Mzuzu Tailors', 'Manufacturing', 'Mzuzu', 3, 'Uniform and workwear tailoring for schools and NGOs.');
        $ml = $this->published($mzuzu, ['amount' => $mk(3000000), 'purpose' => 'Four industrial sewing machines to fill a school uniform order.', 'term_months' => 6, 'proposed_rate_bp' => 2400, 'monthly_revenue' => $mk(1800000), 'monthly_expenses' => $mk(1200000), 'existing_debt' => $mk(200000)], 'Sewing machines for a uniform contract');
        $ag2 = $this->negotiateAndFund($ml, $this->u['investor'], $mzuzu->owner, $mk(3000000), 2400);
        RepaymentSchedule::where('agreement_id', $ag2->id)->get()->each(fn ($r) => $r->update(['due_on' => $r->due_on->subDays(70)]));
        app(RepaymentService::class)->refreshStatus($ag2->fresh());   // first instalments are now past due

        // A finished, rejected application and a conversation in progress on the open listing.
        $rej = $this->business('b_rejected', 'Quick Cash Kiosk (demo)', 'Quick Cash Kiosk', 'Retail & trade', 'Blantyre', 0, 'A new kiosk with no trading history yet.');
        $a = $this->application($rej, ['amount' => $mk(8000000), 'purpose' => 'Start-up stock for a new kiosk with no existing sales or records.', 'term_months' => 12, 'proposed_rate_bp' => 2000, 'monthly_revenue' => $mk(400000), 'monthly_expenses' => $mk(380000), 'existing_debt' => $mk(900000)]);
        Auth::setUser($rej->owner);
        $wf->submit($a, $rej->owner);
        $this->assess($a, 'No trading history; the projected surplus does not cover the instalment.', 'reject');
        Auth::setUser($this->u['approver']);
        $credit->decide($a->fresh(), $this->u['approver'], 'rejected', 'Repayment capacity is insufficient and there is no operating history to rely on.');

        $msg = app(MessagingService::class);
        Auth::setUser($this->u['investor']);
        $c = $msg->start($open, $this->u['investor']);
        $msg->send($c, $this->u['investor'], 'Hello, could you tell me how many layers you have today and what feed costs per month?');
        Auth::setUser($chikondi->owner);
        $msg->send($c, $chikondi->owner, 'Thank you for your interest. We have 3,500 layers today. Feed is about MK 1.4 million a month; the new house adds roughly 40% output.');

        Complaint::create(['user_id' => $this->u['investor2']->id, 'category' => 'service', 'subject' => 'Statement format', 'body' => 'Could the downloadable statement include the agreement reference on each row? (Demo complaint.)']);
        Auth::logout();
    }

    private function assess(Application $a, string $notes, string $rec): void
    {
        $credit = app(CreditService::class);
        $an = $this->u['analyst'];
        Auth::setUser($an);
        $a = $a->fresh();
        $credit->startScreening($a, $an);
        $credit->beginAssessment($a->fresh(), $an);
        $s = $credit->suggest($a->fresh())['score'];
        $credit->assess($a->fresh(), $an, $s, $notes, $rec);
    }

    private function decide(Application $a): void
    {
        Auth::setUser($this->u['approver']);
        app(CreditService::class)->decide($a->fresh(), $this->u['approver'], 'approved', 'Meets policy: adequate cash flow cover and verified documents.', 'Quarterly management accounts to be shared.', null, 3000);
    }

    private function published(Business $b, array $o, string $title): Listing
    {
        $a = $this->application($b, $o);
        Auth::setUser($b->owner);
        app(ApplicationWorkflow::class)->submit($a, $b->owner);
        $this->assess($a, 'Strong repayment capacity and consistent trading history. Documents reviewed and consistent.', 'approve');
        $this->decide($a);
        $l = app(ListingService::class);
        Auth::setUser($this->u['approver']);
        $listing = $l->prepare($a->fresh(), $this->u['approver'], ['title' => $title, 'summary' => $b->description.' Seeking financing for: '.$a->purpose, 'risk_disclosure' => 'Repayment depends on the business continuing to trade profitably. Late or missed payments, partial repayment and loss of capital are possible. Credit assessment is an estimate, not a guarantee.', 'min_investment' => 500000, 'max_investment' => $a->amount, 'deadline' => now()->addDays(30)->toDateString()]);
        Auth::setUser($this->u['executive']);
        $l->review($listing, $this->u['executive']);
        $l->publish($listing->fresh(), $this->u['executive']);

        return $listing->fresh();
    }

    private function negotiateAndFund(Listing $listing, User $investor, User $owner, int $amount, int $rate): LoanAgreement
    {
        $msg = app(MessagingService::class);
        $offers = app(OfferService::class);
        Auth::setUser($investor);
        $c = $msg->start($listing, $investor);
        $msg->send($c, $investor, 'I would like to fund this opportunity. Please review my proposal.');
        $v = $offers->propose($c, $investor, ['principal' => $amount, 'rate_bp' => $rate, 'term_months' => $listing->term_months, 'frequency' => 'monthly', 'interest_method' => 'reducing', 'grace_instalments' => 0]);
        Auth::setUser($owner);
        $a = $offers->accept($v, $owner);

        $pay = app(PaymentService::class);
        $sb = app(SandboxProvider::class);
        Auth::setUser($investor);
        $attempt = $pay->initiate($investor, $a, 'funding', $pay->fundingAmount($a), (string) Str::uuid());
        $hook = $sb->settle($attempt->provider_ref);
        $pay->handleWebhook($hook['body'], $hook['signature']);
        Auth::setUser($this->u['finance']);
        $pay->approveDisbursement(Disbursement::where('agreement_id', $a->id)->firstOrFail(), $this->u['finance']);

        return $a->fresh();
    }

    private function repay(LoanAgreement $a, int $instalments): void
    {
        $pay = app(PaymentService::class);
        $sb = app(SandboxProvider::class);
        $owner = $a->business->owner;
        $rows = $a->schedule()->orderBy('number')->take($instalments)->get();
        Auth::setUser($owner);
        foreach ($rows as $r) {
            $attempt = $pay->initiate($owner, $a, 'repayment', $r->principal_due + $r->interest_due, (string) Str::uuid());
            $hook = $sb->settle($attempt->provider_ref);
            $pay->handleWebhook($hook['body'], $hook['signature']);
        }
    }
}
