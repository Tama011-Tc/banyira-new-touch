<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\LoanApplication;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Loads the demo data set and opens every page as each kind of user, so a
 * broken query, missing prop or missing Vue page fails the build.
 */
class PageSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function visit(User $u, array $urls): void
    {
        foreach ($urls as $url) {
            $res = $u->isEmployee() ? $this->asStaff($u)->get($url) : $this->actingAs($u)->get($url);
            $res->assertOk();
            $res->assertInertia(fn (AssertableInertia $p) => $p->has('auth.user'));
        }
    }

    public function test_business_pages(): void
    {
        $u = User::where('email', 'owner@lilongweprint.example')->first();
        $app = LoanApplication::whereHas('business', fn ($q) => $q->where('user_id', $u->id))->first();
        $c = Conversation::first();
        $this->visit($u, ['/business', '/business/profile', '/business/applications', "/business/applications/{$app->id}", '/business/offers', '/business/financing', '/business/repayments', '/business/messages', '/business/documents', '/business/notifications', '/business/settings', '/business/help']);

        $draftOwner = User::where('email', 'owner@dowapoultry.example')->first();
        $draft = LoanApplication::where('status', 'draft')->first();
        $this->visit($draftOwner, ["/business/applications/{$draft->id}/edit"]);

        $mzuzu = User::where('email', 'owner@mzuzufresh.example')->first();
        $this->visit($mzuzu, ['/business/offers', "/business/messages/{$c->id}"]);

        $this->actingAs($u)->get('/business/agreements/'.Agreement::first()->id.'/statement')->assertOk();
    }

    public function test_investor_pages(): void
    {
        $u = User::where('email', 'investor@example.com')->first();
        $listing = Listing::where('status', 'open')->first();
        $this->visit($u, ['/investor', '/investor/marketplace', '/investor/marketplace?risk=B&term=13to24&sort=rate', "/investor/marketplace/{$listing->id}", '/investor/investments', '/investor/offers', '/investor/agreements', '/investor/repayments', '/investor/transactions', '/investor/messages', '/investor/documents', '/investor/notifications', '/investor/profile', '/investor/settings', '/investor/help']);
    }

    public function test_staff_pages(): void
    {
        $by = fn ($email) => User::where('email', $email)->first();
        $app = LoanApplication::where('status', 'assessment_in_progress')->first();

        $this->visit($by('tadala.phiri@banyira.internal'), ['/internal/credit', '/internal/credit?status=mine', '/internal/credit/history', "/internal/credit/applications/{$app->id}", '/internal/verifications']);
        $this->visit($by('grace.kachale@banyira.internal'), ['/internal/executive', '/internal/executive/risk', '/internal/executive/people', '/internal/approvals', '/internal/rules', '/internal/finance', '/internal/finance/ledger', '/internal/finance/payroll', '/internal/audit']);
        $this->visit($by('blessings.nkhoma@banyira.internal'), ['/internal/finance', '/internal/finance/funding', '/internal/finance/reconciliation', '/internal/finance/approvals', '/internal/finance/ledger', '/internal/finance/ledger?category=client_cash', '/internal/finance/expenses', '/internal/finance/payroll', '/internal/finance/reports']);
        $this->visit($by('mercy.chirwa@banyira.internal'), ['/internal/sales', '/internal/sales/leads', '/internal/sales/leads?stage=new', '/internal/sales/campaigns']);
        $this->visit($by('kondwani.mwale@banyira.internal'), ['/internal/it', '/internal/it/users', '/internal/it/roles', '/internal/it/security', '/internal/it/jobs', '/internal/audit', '/internal/rules', '/internal/products']);

        $admin = $by('kondwani.mwale@banyira.internal');
        $page = \App\Models\Page::first();
        $this->visit($admin, ['/internal', '/internal/profile', '/internal/notifications', '/internal/content', "/internal/content/pages/{$page->id}", "/internal/content/pages/{$page->id}/preview", '/internal/content/media', '/internal/content/settings', "/internal/it/users/{$by('mercy.chirwa@banyira.internal')->id}", '/internal/it/backups', '/internal/it/users?q=chirwa&sort=signin', '/internal/it/security?result=success&q=127', '/internal/audit?area=security&q=signed', '/internal/content?type=builtin&sort=updated']);

        foreach (['transactions', 'income', 'client-funds'] as $t) {
            $this->asStaff($by('blessings.nkhoma@banyira.internal'))->get("/internal/finance/exports/{$t}")->assertOk();
        }
    }

    public function test_demo_ledger_balances(): void
    {
        $row = \DB::table('journal_lines')->selectRaw('SUM(debit) d, SUM(credit) c')->first();
        $this->assertSame(\App\Support\Money::of($row->d), \App\Support\Money::of($row->c));
    }
}
