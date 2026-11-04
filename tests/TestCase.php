<?php

namespace Tests;

use App\Models\AssessmentCriterion;
use App\Models\BusinessProfile;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Role;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\AssessmentService;
use App\Services\MarketplaceService;
use App\Support\Totp;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected string $seeder = ReferenceDataSeeder::class;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('documents');
    }

    protected function business(array $attrs = []): User
    {
        $u = User::create(['name' => 'Test Business', 'email' => uniqid('biz').'@example.com', 'phone' => '0888'.random_int(100000, 999999), 'type' => 'business', 'status' => 'active', 'password' => 'secret123', 'email_verified_at' => now(), 'phone_verified_at' => now(), ...$attrs]);
        $p = new BusinessProfile(['legal_name' => $u->name, 'industry' => 'Retail', 'region' => 'Central Region', 'description' => 'A test business.']);
        $p->user_id = $u->id;
        $p->forceFill(['verification_status' => 'verified'])->save();

        return $u->load('businessProfile');
    }

    protected function investor(bool $verified = true, array $attrs = []): User
    {
        $u = User::create(['name' => 'Test Investor', 'email' => uniqid('inv').'@example.com', 'phone' => '0999'.random_int(100000, 999999), 'type' => 'investor', 'status' => 'active', 'password' => 'secret123', 'email_verified_at' => now(), 'phone_verified_at' => now(), ...$attrs]);
        $u->investorProfile()->create(['investor_type' => 'Individual', 'risk_acknowledged_at' => now()])->forceFill(['verification_status' => $verified ? 'verified' : 'unverified'])->save();

        return $u->load('investorProfile');
    }

    protected function staff(string $role, array $attrs = []): User
    {
        $u = User::create(['name' => ucfirst($role).' Officer', 'email' => uniqid($role).'@banyira.internal', 'type' => 'employee', 'status' => 'active', 'password' => 'Staff#Pass2026', 'email_verified_at' => now(), ...$attrs]);
        $u->roles()->attach(Role::where('slug', $role)->value('id'));
        $u->forceFill(['mfa_secret' => Totp::generateSecret(), 'mfa_confirmed_at' => now()])->save();

        return $u;
    }

    /** Customers use the web guard and employees the staff guard, whatever was used last. */
    public function actingAs($user, $guard = null)
    {
        return parent::actingAs($user, $guard ?? ($user->type === 'employee' ? 'staff' : 'web'));
    }

    /** Act as an employee with a completed MFA session. */
    protected function asStaff(User $u): static
    {
        return $this->actingAs($u, 'staff')->withSession(['staff_mfa_passed' => true, 'staff_last_seen' => time()]);
    }

    protected function submittedApplication(User $owner, string $amount = '2000000', int $months = 12, string $product = 'working-capital'): LoanApplication
    {
        $wf = app(ApplicationWorkflow::class);
        $app = $wf->createDraft($owner, ['loan_product_id' => LoanProduct::where('slug', $product)->value('id'), 'amount' => $amount, 'term_months' => $months, 'purpose' => 'Stock purchase']);
        $app->documents()->create(['type' => 'Bank or mobile money statements', 'original_name' => 's.pdf', 'path' => 'x/s.pdf', 'mime' => 'application/pdf', 'size' => 10, 'uploaded_by' => $owner->id, 'status' => 'pending']);

        return $wf->submit($app->refresh(), $owner);
    }

    /** Runs an application through review, assessment and approval to a live listing. */
    protected function listedApplication(User $owner, array $scores = [4, 4, 4, 4, 4], string $amount = '2000000', int $months = 12)
    {
        $credit = $this->staff('credit_officer');
        $exec = $this->staff('executive');
        $app = $this->submittedApplication($owner, $amount, $months);
        app(ApplicationWorkflow::class)->startReview($app, $credit);
        $as = app(AssessmentService::class);
        $a = $as->start($app->refresh(), $credit);
        $as->save($a, ['scores' => $this->scores($scores), 'recommendation' => 'approve', 'comments' => 'Fine.'], $credit);
        $as->submit($a->refresh(), $credit);
        $as->decide($a->refresh(), true, $exec);

        return app(MarketplaceService::class)->publish($app->refresh()->load('business'), $exec, ['public_name' => 'Listed Co', 'summary' => 'Summary', 'purpose' => 'Stock', 'indicative_rate' => '24']);
    }

    protected function scores(array $values): array
    {
        return array_combine(AssessmentCriterion::orderBy('sort_order')->pluck('id')->take(count($values))->all(), $values);
    }
}
