<?php

namespace Tests\Feature;

use App\Models\PhoneVerificationCode;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_business_registration_then_phone_verification(): void
    {
        $this->post('/register/business', [
            'name' => 'Lilongwe Bakers', 'email' => 'Owner@Bakers.example', 'phone' => '0999 123 456', 'password' => 'bread2026', 'password_confirmation' => 'bread2026',
            'terms' => '1', 'industry' => 'Retail', 'business_type' => 'Sole trader',
        ])->assertRedirect('/verify-phone');

        $user = User::where('email', 'owner@bakers.example')->firstOrFail();
        $this->assertSame('business', $user->type);
        $this->assertNotNull($user->businessProfile);
        $this->get('/business')->assertRedirect('/verify-phone');

        $this->get('/verify-phone')->assertOk();
        PhoneVerificationCode::where('user_id', $user->id)->update(['code_hash' => Hash::make('123456')]);
        $this->post('/verify-phone', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/verify-phone', ['code' => '123456'])->assertRedirect('/onboarding');
        $this->assertNotNull($user->refresh()->phone_verified_at);
        $this->get('/business')->assertOk();
    }

    public function test_investor_registration_requires_risk_acknowledgement(): void
    {
        $this->post('/register/investor', [
            'name' => 'Thoko', 'email' => 't@example.com', 'phone' => '0888123456', 'password' => 'invest2026', 'password_confirmation' => 'invest2026',
            'terms' => '1', 'investor_type' => 'Individual',
        ])->assertSessionHasErrors('risk');
        $this->assertDatabaseMissing('users', ['email' => 't@example.com']);
    }

    public function test_customer_login_is_type_specific_and_excludes_employees(): void
    {
        $biz = $this->business();
        $staff = $this->staff('credit_officer');

        $this->post('/login/investor', ['email' => $biz->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->post('/login/business', ['email' => $staff->email, 'password' => 'Staff#Pass2026'])->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->post('/login/business', ['email' => $biz->email, 'password' => 'secret123'])->assertRedirect('/business');
        $this->assertAuthenticatedAs($biz, 'web');
    }

    public function test_repeated_failures_lock_the_account(): void
    {
        $biz = $this->business();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/business', ['email' => $biz->email, 'password' => 'wrong']);
        }
        $this->assertTrue($biz->refresh()->isLocked());
        $this->post('/login/business', ['email' => $biz->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_logs', ['area' => 'security', 'subject_id' => $biz->id]);
    }

    public function test_staff_sign_in_requires_mfa(): void
    {
        $u = $this->staff('finance_officer');

        $this->post('/internal-access', ['email' => $u->email, 'password' => 'Staff#Pass2026'])->assertRedirect('/internal-access/verify');
        $this->get('/internal/finance')->assertRedirect('/internal-access');      // password alone is not enough

        $this->post('/internal-access', ['email' => $u->email, 'password' => 'Staff#Pass2026']);
        $this->post('/internal-access/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/internal-access/verify', ['code' => Totp::code($u->mfa_secret)])->assertRedirect('/internal');
        $this->get('/internal/finance')->assertOk();
    }

    public function test_new_staff_must_enrol_in_mfa(): void
    {
        $u = $this->staff('sales_marketing');
        $u->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null])->save();

        $this->post('/internal-access', ['email' => $u->email, 'password' => 'Staff#Pass2026'])->assertRedirect('/internal-access/setup');
        $this->get('/internal-access/setup')->assertOk();
        $secret = $u->refresh()->mfa_secret;
        $this->assertNotNull($secret);
        $this->post('/internal-access/setup', ['code' => Totp::code($secret)])->assertRedirect('/internal');
        $this->assertNotNull($u->refresh()->mfa_confirmed_at);
    }

    public function test_customers_cannot_use_the_staff_portal(): void
    {
        $inv = $this->investor();
        $this->post('/internal-access', ['email' => $inv->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->actingAs($inv, 'web')->get('/internal')->assertRedirect('/internal-access');
    }

    public function test_idle_staff_sessions_expire(): void
    {
        $u = $this->staff('it_admin');
        $this->actingAs($u, 'staff')->withSession(['staff_mfa_passed' => true, 'staff_last_seen' => time() - 16 * 60])
            ->get('/internal/it')->assertRedirect('/internal-access');
    }
}
