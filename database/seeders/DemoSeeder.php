<?php

namespace Database\Seeders;

use App\Models\AssessmentCriterion;
use App\Models\BusinessProfile;
use App\Models\Campaign;
use App\Models\Expense;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\PayrollRun;
use App\Models\Role;
use App\Models\User;
use App\Services\AgreementService;
use App\Services\ApplicationWorkflow;
use App\Services\ApprovalService;
use App\Services\AssessmentService;
use App\Services\LedgerService;
use App\Services\MarketplaceService;
use App\Services\MessagingService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * DEVELOPMENT FIXTURES. Clearly fictional businesses, people and amounts,
 * created by running the real services so every number on every dashboard
 * comes from the database. Never run in production.
 */
class DemoSeeder extends Seeder
{
    public const STAFF_PASSWORD = 'Staff#Banyira2026';

    public const CUSTOMER_PASSWORD = 'demo1234';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder refused to run in production.');

            return;
        }

        $s = app()->make(...);
        $wf = $s(ApplicationWorkflow::class);
        $as = $s(AssessmentService::class);
        $mk = $s(MarketplaceService::class);
        $ag = $s(AgreementService::class);
        $ap = $s(ApprovalService::class);
        $pay = $s(PaymentService::class);

        // ---- Staff ----
        $credit = $this->staff('Tadala Phiri', 'tadala.phiri@banyira.internal', 'credit_officer', 'BNY-001', 'Credit', 'Credit Assessment Officer');
        $this->staff('Kondwani Mwale', 'kondwani.mwale@banyira.internal', 'it_admin', 'BNY-002', 'Technology', 'IT Administrator');
        $sales = $this->staff('Mercy Chirwa', 'mercy.chirwa@banyira.internal', 'sales_marketing', 'BNY-003', 'Sales and Marketing', 'Sales and Marketing Specialist');
        $fin1 = $this->staff('Blessings Nkhoma', 'blessings.nkhoma@banyira.internal', 'finance_officer', 'BNY-004', 'Finance', 'Finance Officer');
        $fin2 = $this->staff('Chimwemwe Gondwe', 'chimwemwe.gondwe@banyira.internal', 'finance_officer', 'BNY-005', 'Finance', 'Finance Officer');
        $exec = $this->staff('Grace Kachale', 'grace.kachale@banyira.internal', 'executive', 'BNY-006', 'Executive', 'Chief Executive Officer');

        // ---- Investors ----
        $inv = [
            $this->investor('Chikondi Banda', 'investor@example.com', '0999100001'),
            $this->investor('Thoko Mvula', 'thoko.mvula@example.com', '0999100002'),
            $this->investor('Peter Mhone', 'peter.mhone@example.com', '0999100003'),
            $this->investor('Agnes Kamanga', 'agnes.kamanga@example.com', '0999100004'),
        ];

        // ---- Businesses and their applications ----
        $p = LoanProduct::pluck('id', 'slug');
        $mzuzu = $this->business('Mzuzu Fresh Foods', 'owner@mzuzufresh.example', 'Agriculture', 'Northern Region', 'Mzuzu', 'Packs and distributes vegetables and fruit to supermarkets and lodges.');
        $print = $this->business('Lilongwe Print House', 'owner@lilongweprint.example', 'Manufacturing', 'Central Region', 'Lilongwe', 'Commercial printing for schools, NGOs and local businesses.');
        $blantyre = $this->business('Blantyre Logistics', 'owner@blantyrelogistics.example', 'Transportation', 'Southern Region', 'Blantyre', 'Regional goods delivery for small retailers.');
        $zomba = $this->business('Zomba Hardware and Supplies', 'owner@zombahardware.example', 'Retail', 'Southern Region', 'Zomba', 'Building materials and hardware for local contractors.');
        $shire = $this->business('Shire Valley Agro', 'owner@shirevalley.example', 'Agriculture', 'Southern Region', 'Nsanje', 'Aggregates and stores smallholder produce.');
        $kasungu = $this->business('Kasungu Tailoring Collective', 'owner@kasungutailoring.example', 'Manufacturing', 'Central Region', 'Kasungu', 'Makes uniforms for schools and security firms.');
        $salima = $this->business('Salima Lakeside Lodge', 'owner@salimalodge.example', 'Tourism', 'Central Region', 'Salima', 'Small lakeshore lodge with a restaurant and conference room.');
        $dowa = $this->business('Dowa Poultry Farm', 'owner@dowapoultry.example', 'Agriculture', 'Central Region', 'Dowa', 'Layers and broilers for Lilongwe markets.');

        $app = fn (User $u, string $product, string $amount, int $months, string $purpose) => $this->application($wf, $u, $p[$product], $amount, $months, $purpose);

        // Fully funded, signed, funded, disbursed, partly repaid.
        $a1 = $app($print, 'equipment', '6000000', 18, 'Purchase of a digital printing press');
        $l1 = $this->approveAndList($wf, $as, $mk, $a1, $credit, $exec, [5, 4, 4, 4, 4], '22');
        foreach ([[$inv[0], '2500000', '21', 18], [$inv[1], '2000000', '22', 18], [$inv[2], '1500000', '23', 18]] as [$who, $amt, $rate, $mo]) {
            $mk->accept($mk->makeOffer($l1, $who, ['amount' => $amt, 'annual_rate' => $rate, 'term_months' => $mo]), $print);
        }
        $agr = $l1->refresh()->agreement;
        foreach ($agr->parties()->with('user')->get() as $party) {
            if (! $party->signed_at) {
                $ag->sign($agr->refresh(), $party->user);
            }
        }
        foreach ($agr->parties()->where('role', 'investor')->get() as $party) {
            $ag->confirmInvestorFunds($party, $fin1, 'MM-'.random_int(100000, 999999));
        }
        $req = $ap->request('disbursement', $agr->refresh(), "Disburse {$agr->reference} to Lilongwe Print House", $agr->principal_total, $fin1);
        $ap->decide($req, true, $fin2, 'Funds and signatures verified');
        foreach (['repay-demo-1', 'repay-demo-2'] as $key) {
            $agr->refresh();
            $instalment = $agr->schedule()->where('status', '!=', 'paid')->orderBy('due_date')->get()->groupBy(fn ($r) => $r->due_date->toDateString())->first();
            $amount = $instalment->reduce(fn ($c, $r) => bcadd($c, $r->amountOutstanding(), 2), '0');
            $pay->confirm($pay->initiateRepayment($agr, $print, $amount, $key, '0888200002')['payment'], $fin1, 'MM-'.random_int(100000, 999999));
        }
        $pay->initiateRepayment($agr->refresh(), $print, '400000', 'repay-demo-3', '0888200002');   // pending, awaiting reconciliation

        // Listed with competing offers (the main business demo account).
        $a2 = $app($mzuzu, 'equipment', '4500000', 18, 'Cold-room equipment to reduce spoilage');
        $l2 = $this->approveAndList($wf, $as, $mk, $a2, $credit, $exec, [4, 3, 3, 4, 3], '28');
        $mk->accept($mk->makeOffer($l2, $inv[3], ['amount' => '700000', 'annual_rate' => '28', 'term_months' => 18]), $mzuzu);
        $mk->makeOffer($l2, $inv[1], ['amount' => '1000000', 'annual_rate' => '24', 'term_months' => 18, 'conditions' => 'Monthly sales summary shared with investors.']);
        $mk->makeOffer($l2, $inv[2], ['amount' => '600000', 'annual_rate' => '27', 'term_months' => 12]);
        $mk->makeOffer($l2, $inv[0], ['amount' => '500000', 'annual_rate' => '22', 'term_months' => 18]);

        // Listed, open to investors.
        $a3 = $app($blantyre, 'equipment', '12000000', 36, 'Two delivery vehicles');
        $l3 = $this->approveAndList($wf, $as, $mk, $a3, $credit, $exec, [5, 5, 4, 4, 5], '18');
        $mk->accept($mk->makeOffer($l3, $inv[1], ['amount' => '3000000', 'annual_rate' => '18', 'term_months' => 36]), $blantyre);

        // Pipeline at every stage for the credit team.
        $app($zomba, 'working-capital', '2000000', 9, 'Bulk cement and roofing stock ahead of the building season');           // submitted
        $a5 = $app($shire, 'short-term', '2500000', 12, 'Seasonal working capital for the maize buying season');
        $wf->startReview($a5, $credit);
        $wf->requestInformation($a5, $credit, 'Please upload warehouse receipts for last season and your buyer contracts.');
        $a6 = $app($kasungu, 'equipment', '1800000', 15, 'Four industrial sewing machines');
        $wf->startReview($a6, $credit);
        $as->save($as->start($a6->refresh(), $credit), ['scores' => $this->scores([4, 3, 3]), 'checks' => ['identity' => true, 'registration' => true]], $credit);
        $a7 = $app($salima, 'expansion', '5000000', 24, 'Refurbish eight guest rooms');
        $wf->startReview($a7, $credit);
        $assess = $as->start($a7->refresh(), $credit);
        $as->save($assess, ['scores' => $this->scores([4, 4, 4, 3, 4]), 'checks' => $this->allChecks(), 'recommendation' => 'approve', 'comments' => 'Strong occupancy records. Refurbishment quotations verified.'], $credit);
        $as->submit($assess->refresh(), $credit);                                                                                                      // awaits executive decision
        $wf->createDraft($dowa, ['loan_product_id' => $p['working-capital'], 'amount' => '1200000', 'term_months' => 12, 'purpose' => 'Feed and day-old chicks']);

        // ---- Finance: expenses and payroll awaiting the checker ----
        foreach ([['Premises', 'Office rent, Lilongwe', '400000'], ['Technology', 'Hosting and SMS credits', '310000'], ['Marketing', 'Radio campaign, Mzuzu', '280000']] as $i => [$cat, $desc, $amt]) {
            $e = Expense::create(['category' => $cat, 'description' => $desc, 'amount' => $amt, 'incurred_on' => now()->subDays(10 - $i)->toDateString(), 'recorded_by' => $fin1->id, 'status' => 'pending_approval']);
            $r = $ap->request('expense', $e, "Expense: {$desc}", $amt, $fin1);
            if ($i < 2) {
                $ap->decide($r, true, $fin2);
            }
        }
        $run = PayrollRun::create(['period' => now()->startOfMonth()->toDateString(), 'total_gross' => '0', 'total_net' => '0', 'prepared_by' => $fin2->id, 'status' => 'pending_approval']);
        $gross = '0';
        $net = '0';
        foreach (User::where('type', 'employee')->get() as $i => $emp) {
            $g = (string) (650000 + $i * 75000);
            $n = bcmul($g, '0.82', 2);
            $run->items()->create(['user_id' => $emp->id, 'gross' => $g, 'deductions' => bcsub($g, $n, 2), 'net' => $n]);
            $gross = bcadd($gross, $g, 2);
            $net = bcadd($net, $n, 2);
        }
        $run->update(['total_gross' => $gross, 'total_net' => $net]);
        $ap->request('payroll_run', $run, 'Payroll '.now()->format('F Y'), $gross, $fin2);
        app(LedgerService::class)->post('Owner capital introduced', [['1010', '5000000', '0'], ['3010', '0', '5000000']], null, $fin1, $fin2, now()->startOfYear()->toDateString());

        // ---- Sales and marketing ----
        $camp = Campaign::create(['name' => 'Mzuzu SME outreach', 'channel' => 'SMS and radio', 'audience' => 'businesses', 'status' => 'running', 'starts_on' => now()->subWeeks(3), 'ends_on' => now()->addWeeks(3), 'reach' => 2400, 'budget' => '280000', 'created_by' => $sales->id]);
        Campaign::create(['name' => 'First-time investors', 'channel' => 'Facebook', 'audience' => 'investors', 'status' => 'running', 'starts_on' => now()->subWeeks(2), 'ends_on' => now()->addWeeks(4), 'reach' => 5100, 'budget' => '350000', 'created_by' => $sales->id]);
        Campaign::create(['name' => 'Agro season stock', 'channel' => 'WhatsApp', 'audience' => 'businesses', 'status' => 'scheduled', 'starts_on' => now()->addWeek(), 'reach' => 0, 'budget' => '120000', 'created_by' => $sales->id]);
        foreach ([['Nkhata Bay Cold Storage', 'business', 'Referral', 'contacted'], ['Joseph Phiri', 'investor', 'Facebook', 'new'], ['Mulanje Tea Packers', 'business', 'Radio', 'qualified'], ['Ruth Banda', 'investor', 'Event', 'onboarding'], ['Lunzu Bakery', 'business', 'WhatsApp', 'lost']] as $i => [$name, $type, $src, $stage]) {
            $lead = Lead::create(['name' => $name, 'type' => $type, 'source' => $src, 'stage' => $stage, 'owner_id' => $sales->id, 'campaign_id' => $type === 'business' ? $camp->id : null, 'next_follow_up' => $stage === 'lost' ? null : now()->addDays($i + 1)]);
            $lead->activities()->create(['user_id' => $sales->id, 'kind' => 'note', 'note' => 'Lead created from '.$src]);
        }

        // ---- Messages ----
        $msg = app(MessagingService::class);
        $c = $msg->conversationFor($inv[1], $l2->refresh()->load('application.business.user'));
        $msg->send($c, $inv[1], 'Hello. Could you share how much stock you lose to spoilage each month today?');
        $msg->send($c, $mzuzu, 'Roughly 12% in the hot months. The cold room should bring that under 3%. I have uploaded the supplier quotation.');

        $this->command?->info('Demo data created. Customer password: '.self::CUSTOMER_PASSWORD.' · Staff password: '.self::STAFF_PASSWORD.' (MFA is set up at first sign-in).');
    }

    private function staff(string $name, string $email, string $role, string $no, string $dept, string $title): User
    {
        $u = User::create(['name' => $name, 'email' => $email, 'phone' => null, 'type' => 'employee', 'status' => 'active', 'password' => self::STAFF_PASSWORD, 'email_verified_at' => now(), 'password_changed_at' => now()]);
        $u->roles()->attach(Role::where('slug', $role)->value('id'));
        $u->employeeProfile()->create(['staff_number' => $no, 'department' => $dept, 'job_title' => $title]);

        return $u;
    }

    private function investor(string $name, string $email, string $phone): User
    {
        $u = User::create(['name' => $name, 'email' => $email, 'phone' => $phone, 'type' => 'investor', 'status' => 'active', 'password' => self::CUSTOMER_PASSWORD, 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $profile = $u->investorProfile()->create(['investor_type' => 'Individual', 'source_of_funds' => 'Salary', 'payout_method' => 'Mobile money', 'risk_acknowledged_at' => now()]);
        $profile->forceFill(['verification_status' => 'verified', 'verified_at' => now()])->save();
        foreach (['email', 'phone', 'identity', 'source_of_funds'] as $k) {
            $u->verifications()->create(['kind' => $k, 'status' => 'verified', 'reviewed_at' => now()]);
        }

        return $u;
    }

    private function business(string $name, string $email, string $industry, string $region, string $district, string $desc): User
    {
        static $n = 0;
        $n++;
        $u = User::create(['name' => $name, 'email' => $email, 'phone' => '08882000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'type' => 'business', 'status' => 'active', 'password' => self::CUSTOMER_PASSWORD, 'email_verified_at' => now(), 'phone_verified_at' => now()]);
        $bp = new BusinessProfile(['legal_name' => $name, 'business_type' => 'Private limited company', 'industry' => $industry, 'region' => $region, 'district' => $district, 'address' => $district.', Malawi', 'years_operating' => 3 + $n % 4, 'employees' => 6 + $n * 2, 'monthly_turnover' => (string) (1500000 + $n * 250000), 'description' => $desc, 'registration_number' => 'BRN-'.(10200 + $n)]);
        $bp->user_id = $u->id;
        $bp->forceFill(['verification_status' => 'verified', 'verified_at' => now()])->save();
        foreach (['email', 'phone', 'business_registration', 'identity'] as $k) {
            $u->verifications()->create(['kind' => $k, 'status' => 'verified', 'reviewed_at' => now()]);
        }

        return $u->setRelation('businessProfile', $bp);
    }

    private function application(ApplicationWorkflow $wf, User $owner, int $productId, string $amount, int $months, string $purpose): LoanApplication
    {
        $a = $wf->createDraft($owner, ['loan_product_id' => $productId, 'amount' => $amount, 'term_months' => $months, 'purpose' => $purpose, 'monthly_turnover' => $owner->businessProfile->monthly_turnover, 'employees' => $owner->businessProfile->employees, 'main_customers' => 'Mixed']);
        $path = "applications/{$a->id}/statement.pdf";
        Storage::disk(config('banyira.documents_disk'))->put($path, "%PDF-1.4\n% Demonstration document for {$owner->name}\n");
        $a->documents()->create(['type' => 'Bank or mobile money statements', 'original_name' => 'statements-6-months.pdf', 'path' => $path, 'mime' => 'application/pdf', 'size' => 64, 'uploaded_by' => $owner->id, 'status' => 'accepted']);
        for ($i = 6; $i >= 1; $i--) {
            $in = 1400000 + random_int(0, 6) * 100000;
            $a->financialRecords()->create(['period' => now()->subMonths($i)->startOfMonth(), 'inflow' => $in, 'outflow' => (int) ($in * 0.74)]);
        }

        return $wf->submit($a->refresh(), $owner);
    }

    private function approveAndList(ApplicationWorkflow $wf, AssessmentService $as, MarketplaceService $mk, LoanApplication $app, User $credit, User $exec, array $scores, string $rate)
    {
        $wf->startReview($app, $credit);
        $a = $as->start($app->refresh(), $credit);
        $as->save($a, ['scores' => $this->scores($scores), 'checks' => $this->allChecks(), 'recommendation' => 'approve', 'comments' => 'Documents verified. Cash flow supports the proposed instalments.'], $credit);
        $as->submit($a->refresh(), $credit);
        $as->decide($a->refresh(), true, $exec, 'Approved per recommendation');
        $app->refresh()->load('business');

        return $mk->publish($app, $exec, ['public_name' => $app->business->legal_name, 'summary' => $app->business->description, 'purpose' => $app->purpose, 'indicative_rate' => $rate])->load('application.business.user');
    }

    private function scores(array $values): array
    {
        $ids = AssessmentCriterion::orderBy('sort_order')->pluck('id')->all();

        return array_combine(array_slice($ids, 0, count($values)), $values);
    }

    private function allChecks(): array
    {
        return ['identity' => true, 'registration' => true, 'address' => true, 'statements' => true, 'quotation' => true, 'inflows_reconcile' => true];
    }
}
