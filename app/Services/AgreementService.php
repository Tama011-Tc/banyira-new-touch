<?php

namespace App\Services;

use App\Models\InvestmentCommitment;
use App\Models\LoanAgreement;
use App\Models\OfferVersion;
use App\Models\RepaymentSchedule;
use App\Support\LoanCalculator;

class AgreementService
{
    public function __construct(private readonly AuditLogger $audit, private readonly ApplicationWorkflow $workflow) {}

    /** A simulated agreement: terms frozen from the accepted version, schedule generated, commitment pledged. */
    public function createFromOffer(OfferVersion $v): LoanAgreement
    {
        $c = $v->offer->conversation;
        $listing = $c->listing;
        $calc = LoanCalculator::schedule($v->principal, $v->rate_bp, $v->term_months, $v->frequency, $v->interest_method, $v->grace_instalments, now()->toDateString());

        $a = LoanAgreement::create([
            'offer_version_id' => $v->id, 'listing_id' => $listing->id, 'application_id' => $listing->application_id, 'investor_id' => $c->investor_id, 'business_id' => $c->business_id,
            'principal' => $v->principal, 'rate_bp' => $v->rate_bp, 'term_months' => $v->term_months, 'frequency' => $v->frequency, 'interest_method' => $v->interest_method,
            'grace_instalments' => $v->grace_instalments, 'total_interest' => $calc['total_interest'], 'total_payable' => $calc['total_payable'], 'fees' => $v->fees,
            'status' => 'pending_funding', 'simulated' => true, 'agreement_hash' => $v->content_hash, 'first_due_on' => $calc['instalments'][0]['due_on'],
        ]);
        foreach ($calc['instalments'] as $i) {
            RepaymentSchedule::create(['agreement_id' => $a->id, 'number' => $i['number'], 'due_on' => $i['due_on'], 'principal_due' => $i['principal'], 'interest_due' => $i['interest']]);
        }
        InvestmentCommitment::create(['agreement_id' => $a->id, 'investor_id' => $c->investor_id, 'amount' => $v->principal, 'status' => 'pledged', 'risk_acknowledged_at' => $c->investor->investorProfile->risk_acknowledged_at]);

        $app = $listing->application;
        if ($app->status === 'published') {
            $this->workflow->transition($app, 'funding', null, 'First agreement signed');
        }
        foreach ([$c->investor, $c->business->owner] as $party) {
            $party && app(\App\Services\Sms\SmsService::class)->send($party, 'agreement_created', config('banyira.brand').': both sides accepted the terms. Open the app to see the agreement and repayment schedule.');
        }
        $this->audit->record('agreement.created', $a, ['principal' => $a->principal, 'simulated' => true], null);

        return $a;
    }
}
