<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/** Behaviour that must be the same on MySQL and Postgres. */
class DatabasePortabilityTest extends TestCase
{
    public function test_marketplace_search_ignores_letter_case(): void
    {
        $this->listedApplication($this->business());

        $this->actingAs($this->investor())->get('/investor/marketplace?q=LISTED+co')
            ->assertInertia(fn (AssertableInertia $p) => $p->has('listings.data', 1));
    }

    public function test_a_record_url_that_is_not_a_number_is_not_found(): void
    {
        $listing = $this->listedApplication($this->business());

        $this->actingAs($this->investor())->get("/investor/marketplace/{$listing->id}abc")->assertNotFound();
    }

    public function test_a_provider_reference_cannot_be_reused_in_another_letter_case(): void
    {
        $finance = $this->staff('finance_officer');
        $this->payment()->update(['provider_reference' => 'mm-abc123']);
        $second = $this->payment();

        $this->asStaff($finance)->post("/internal/finance/payments/{$second->id}/confirm", ['provider_reference' => 'MM-Abc123'])
            ->assertSessionHasErrors('provider_reference');
        $this->assertSame('pending', $second->refresh()->status);
    }

    public function test_an_employee_email_is_unique_in_any_letter_case(): void
    {
        $admin = $this->staff('it_admin');
        $existing = $this->staff('credit_officer', ['email' => 'jane.banda@banyira.internal']);

        $this->asStaff($admin)->post('/internal/it/users', ['email' => 'Jane.Banda@banyira.internal'])
            ->assertSessionHasErrors('email');
        $this->assertModelExists($existing);
    }

    private function payment(): Payment
    {
        $investor = $this->investor();

        return Payment::create([
            'reference' => 'PAY-'.Str::random(6), 'idempotency_key' => (string) Str::uuid(), 'payer_id' => $investor->id,
            'direction' => 'in', 'purpose' => 'repayment', 'amount' => '1000.00', 'provider' => 'demo', 'status' => 'pending',
        ]);
    }
}
