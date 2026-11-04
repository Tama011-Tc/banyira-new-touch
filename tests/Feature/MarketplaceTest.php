<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\InvestmentOffer;
use App\Services\MarketplaceService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    private function offer($listing, $investor, array $data, int $status = 302)
    {
        return $this->actingAs($investor)->post("/investor/marketplace/{$listing->id}/offers", [...$data, 'accept_risk' => '1']);
    }

    public function test_multiple_investors_fund_one_business_exactly(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner, amount: '5000000', months: 12);
        $inv = [$this->investor(), $this->investor(), $this->investor()];

        foreach ([['1000000', '20'], ['2000000', '22'], ['2000000', '24']] as $i => [$amt, $rate]) {
            $this->offer($listing, $inv[$i], ['amount' => $amt, 'annual_rate' => $rate, 'term_months' => 12])->assertSessionHasNoErrors();
        }
        foreach (InvestmentOffer::orderBy('id')->get() as $o) {
            $this->actingAs($owner)->post("/business/offers/{$o->id}/accept")->assertSessionHasNoErrors();
        }

        $listing->refresh();
        $this->assertSame('5000000.00', $listing->amount_accepted);
        $this->assertSame('0.00', $listing->remaining());
        $this->assertSame('fully_funded', $listing->status);
        $this->assertSame('fully_funded', $listing->application->status);

        $agreement = Agreement::firstOrFail();
        $this->assertSame('5000000.00', $agreement->principal_total);
        $this->assertSame('4.00', $agreement->fee_rate);           // MK5m is not below the MK5m tier boundary
        $this->assertSame(4, $agreement->parties()->count());      // borrower + three investors
    }

    public function test_acceptance_cannot_overfund(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner, amount: '2000000');
        $a = $this->investor();
        $b = $this->investor();
        $this->offer($listing, $a, ['amount' => '1500000', 'annual_rate' => '20', 'term_months' => 12]);
        $this->offer($listing, $b, ['amount' => '1000000', 'annual_rate' => '20', 'term_months' => 12]);   // both pending; together they exceed the request
        [$first, $second] = InvestmentOffer::orderBy('id')->get()->all();

        $this->actingAs($owner)->post("/business/offers/{$first->id}/accept")->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/business/offers/{$second->id}/accept")->assertSessionHasErrors('offer');

        $this->assertSame('1500000.00', $listing->refresh()->amount_accepted);
        $this->assertSame('pending', $second->refresh()->status);
    }

    public function test_offer_rules_are_enforced(): void
    {
        $listing = $this->listedApplication($this->business(), scores: [3, 3, 3, 3, 3], amount: '2000000', months: 12);   // score 60 → class B, max 24 months
        $this->assertSame('B', $listing->risk_class);
        $inv = $this->investor();

        $this->offer($listing, $inv, ['amount' => '10000', 'annual_rate' => '20', 'term_months' => 12])->assertSessionHasErrors('amount');
        $this->offer($listing, $inv, ['amount' => '3000000', 'annual_rate' => '20', 'term_months' => 12])->assertSessionHasErrors('amount');
        $this->offer($listing, $inv, ['amount' => '500000', 'annual_rate' => '37', 'term_months' => 12])->assertSessionHasErrors('annual_rate');
        $this->offer($listing, $inv, ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 30])->assertSessionHasErrors('term_months');
        $this->actingAs($inv)->post("/investor/marketplace/{$listing->id}/offers", ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 12])->assertSessionHasErrors('accept_risk');
        $this->offer($listing, $this->investor(verified: false), ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 12])->assertSessionHasErrors('amount');
        $this->assertSame(0, InvestmentOffer::count());

        $this->offer($listing, $inv, ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 12])->assertSessionHasNoErrors();
        $this->offer($listing, $inv, ['amount' => '500000', 'annual_rate' => '21', 'term_months' => 12])->assertSessionHasErrors('amount');   // one pending offer at a time
    }

    public function test_withdrawal_and_ownership(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner);
        $inv = $this->investor();
        $other = $this->investor();
        $this->offer($listing, $inv, ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 12]);
        $offer = InvestmentOffer::firstOrFail();

        $this->actingAs($other)->post("/investor/offers/{$offer->id}/withdraw")->assertSessionHasErrors('offer');
        $this->actingAs($this->business())->post("/business/offers/{$offer->id}/accept")->assertSessionHasErrors('offer');
        $this->actingAs($inv)->post("/investor/offers/{$offer->id}/withdraw")->assertSessionHasNoErrors();
        $this->assertSame('withdrawn', $offer->refresh()->status);
        $this->actingAs($owner)->post("/business/offers/{$offer->id}/accept")->assertSessionHasErrors('offer');
    }

    public function test_partial_funding_close_creates_agreement_and_expires_offers(): void
    {
        $owner = $this->business();
        $listing = $this->listedApplication($owner, amount: '3000000');
        $this->offer($listing, $this->investor(), ['amount' => '1000000', 'annual_rate' => '20', 'term_months' => 12]);
        $this->offer($listing, $this->investor(), ['amount' => '500000', 'annual_rate' => '25', 'term_months' => 12]);
        [$keep, $leave] = InvestmentOffer::orderBy('id')->get()->all();
        $this->actingAs($owner)->post("/business/offers/{$keep->id}/accept");

        $this->actingAs($owner)->post("/business/listings/{$listing->id}/close")->assertRedirect('/business/financing');
        $this->assertSame('closed', $listing->refresh()->status);
        $this->assertSame('expired', $leave->refresh()->status);
        $this->assertSame('1000000.00', Agreement::firstOrFail()->principal_total);
        $this->assertSame('10.00', Agreement::first()->fee_rate);   // MK1m sits in the "below MK5m" tier
    }

    public function test_stale_offers_expire(): void
    {
        $listing = $this->listedApplication($this->business());
        $this->offer($listing, $this->investor(), ['amount' => '500000', 'annual_rate' => '20', 'term_months' => 12]);
        InvestmentOffer::query()->update(['expires_at' => now()->subMinute()]);
        $this->assertSame(1, app(MarketplaceService::class)->expireStale());
        $this->assertSame('expired', InvestmentOffer::first()->status);

        $this->expectException(ValidationException::class);
        app(MarketplaceService::class)->accept(InvestmentOffer::first(), $listing->application->business->user->load('businessProfile'));
    }

    public function test_investors_never_receive_confidential_business_fields(): void
    {
        $owner = $this->business();
        $owner->businessProfile->update(['registration_number' => 'BRN-SECRET-1', 'address' => 'Plot 99 Secret Road']);
        $listing = $this->listedApplication($owner);

        $this->actingAs($this->investor())->get("/investor/marketplace/{$listing->id}")
            ->assertOk()->assertDontSee('BRN-SECRET-1')->assertDontSee('Plot 99 Secret Road')->assertDontSee($owner->email)->assertDontSee($owner->phone);
    }
}
