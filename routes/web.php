<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\StaffAuthController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Business\ApplicationController;
use App\Http\Controllers\Business\BusinessController;
use App\Http\Controllers\Business\FinancingController;
use App\Http\Controllers\Business\OfferController;
use App\Http\Controllers\Customer\AccountController;
use App\Http\Controllers\Investor\InvestorController;
use App\Http\Controllers\Investor\MarketplaceController;
use App\Http\Controllers\Investor\PortfolioController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Staff;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- Public site
Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'home');
    Route::get('/products', 'products');
    Route::get('/products/{product:slug}', 'product');
    Route::get('/how-to-get-a-loan', 'loan');
    Route::get('/how-to-invest', 'invest');
    Route::get('/about', fn () => app(PublicController::class)->page('About'));
    Route::get('/contact', fn () => app(PublicController::class)->page('Contact'));
    Route::get('/legal', fn () => app(PublicController::class)->page('Legal'));
    Route::post('/contact', 'contact')->middleware('throttle:5,10');
});

Route::post('/webhooks/payments', PaymentWebhookController::class)->middleware('throttle:120,1');

// ------------------------------------------------------- Customer authentication
$type = '^(business|investor)$';
Route::middleware('guest:web')->group(function () use ($type) {
    Route::get('/login', fn () => app(CustomerAuthController::class)->choose('login'))->name('login');
    Route::get('/register', fn () => app(CustomerAuthController::class)->choose('register'));
    Route::get('/login/{type}', [CustomerAuthController::class, 'showLogin'])->where('type', $type);
    Route::post('/login/{type}', [CustomerAuthController::class, 'login'])->where('type', $type)->middleware('throttle:customer-login');
    Route::get('/register/{type}', [CustomerAuthController::class, 'showRegister'])->where('type', $type);
    Route::post('/register/{type}', [CustomerAuthController::class, 'register'])->where('type', $type)->middleware('throttle:10,60');
    Route::get('/forgot-password', [PasswordResetController::class, 'create']);
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,10');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update']);
});

Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [CustomerAuthController::class, 'logout']);
    Route::get('/verify-phone', [VerificationController::class, 'showPhone']);
    Route::post('/verify-phone', [VerificationController::class, 'verifyPhone'])->middleware('throttle:10,10');
    Route::post('/verify-phone/resend', [VerificationController::class, 'resend'])->middleware('throttle:sms-code');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resendEmail'])->middleware('throttle:3,10');
    Route::get('/onboarding', [OnboardingController::class, 'show']);
    Route::post('/onboarding', [OnboardingController::class, 'store']);
});

// Pages both customer types share.
$shared = function () {
    Route::get('/messages/{conversation?}', [AccountController::class, 'messages']);
    Route::post('/messages/{conversation}', [AccountController::class, 'send'])->middleware('throttle:30,1');
    Route::get('/attachments/{attachment}', [AccountController::class, 'attachment']);
    Route::get('/notifications', [AccountController::class, 'notifications']);
    Route::post('/notifications/read', [AccountController::class, 'readNotifications']);
    Route::get('/documents', [AccountController::class, 'documents']);
    Route::get('/settings', [AccountController::class, 'settings']);
    Route::put('/settings/password', [AccountController::class, 'password']);
    Route::get('/help', [AccountController::class, 'help']);
    Route::post('/help', [AccountController::class, 'support'])->middleware('throttle:5,10');
};

// ----------------------------------------------------------- Business dashboard
Route::middleware(['auth:web', 'customer:business'])->prefix('business')->group(function () use ($shared) {
    Route::get('/', [BusinessController::class, 'overview']);
    Route::get('/profile', [BusinessController::class, 'profile']);
    Route::put('/profile', [BusinessController::class, 'updateProfile']);

    Route::controller(ApplicationController::class)->group(function () {
        Route::get('/applications', 'index');
        Route::post('/applications', 'store');
        Route::get('/applications/{application}', 'show');
        Route::get('/applications/{application}/edit', 'edit');
        Route::put('/applications/{application}', 'update');
        Route::delete('/applications/{application}', 'destroy');
        Route::post('/applications/{application}/documents', 'upload');
        Route::delete('/applications/{application}/documents/{document}', 'removeDocument');
        Route::post('/applications/{application}/submit', 'submit');
        Route::post('/information-requests/{informationRequest}', 'respond');
    });

    Route::get('/offers', [OfferController::class, 'index']);
    Route::post('/offers/{offer}/accept', [OfferController::class, 'accept']);
    Route::post('/offers/{offer}/reject', [OfferController::class, 'reject']);
    Route::post('/listings/{listing}/close', [OfferController::class, 'close']);

    Route::get('/financing', [FinancingController::class, 'index']);
    Route::post('/agreements/{agreement}/sign', [FinancingController::class, 'sign']);
    Route::get('/agreements/{agreement}/statement', [FinancingController::class, 'statement']);
    Route::get('/repayments', [FinancingController::class, 'repayments']);
    Route::post('/agreements/{agreement}/payments', [FinancingController::class, 'pay'])->middleware('throttle:10,1');
    Route::get('/documents/{document}', [AccountController::class, 'download']);

    $shared();
});

// ----------------------------------------------------------- Investor dashboard
Route::middleware(['auth:web', 'customer:investor'])->prefix('investor')->group(function () use ($shared) {
    Route::get('/', [InvestorController::class, 'overview']);
    Route::get('/profile', [InvestorController::class, 'profile']);

    Route::get('/marketplace', [MarketplaceController::class, 'index']);
    Route::get('/marketplace/{listing}', [MarketplaceController::class, 'show']);
    Route::post('/marketplace/{listing}/offers', [MarketplaceController::class, 'offer'])->middleware('throttle:20,1');
    Route::post('/marketplace/{listing}/message', [MarketplaceController::class, 'message'])->middleware('throttle:10,1');

    Route::controller(PortfolioController::class)->group(function () {
        Route::get('/investments', 'investments');
        Route::get('/offers', 'offers');
        Route::post('/offers/{offer}/withdraw', 'withdraw');
        Route::get('/agreements', 'agreements');
        Route::post('/agreements/{agreement}/sign', 'sign');
        Route::get('/repayments', 'repayments');
        Route::get('/transactions', 'transactions');
    });

    $shared();
});

// --------------------------------------------------------- Employee portal
// Not linked from the public site. Protection comes from the staff guard,
// MFA, rate limits, lockout, idle timeout and per-permission checks.
Route::prefix('internal-access')->controller(StaffAuthController::class)->group(function () {
    Route::get('/', 'create');
    Route::post('/', 'store')->middleware('throttle:staff-login');
    Route::middleware('auth:staff')->group(function () {
        Route::get('/setup', 'showSetup');
        Route::post('/setup', 'confirmSetup')->middleware('throttle:staff-login');
        Route::get('/verify', 'showVerify');
        Route::post('/verify', 'verify')->middleware('throttle:staff-login');
        Route::post('/logout', 'destroy');
    });
});

Route::middleware(['auth:staff', 'staff'])->prefix('internal')->group(function () {
    Route::get('/', Staff\HomeController::class);

    Route::middleware('can:applications.assess')->controller(Staff\CreditController::class)->group(function () {
        Route::get('/credit', 'queue');
        Route::get('/credit/history', 'history');
        Route::post('/credit/applications/{application}/review', 'review');
        Route::post('/credit/applications/{application}/information', 'requestInformation');
        Route::post('/credit/applications/{application}/assessment', 'startAssessment');
        Route::put('/credit/assessments/{assessment}', 'saveAssessment');
        Route::post('/credit/documents/{document}/review', 'reviewDocument');
    });
    Route::middleware('can:applications.view')->group(function () {
        Route::get('/credit/applications/{application}', [Staff\CreditController::class, 'show']);
        Route::get('/credit/documents/{document}', [Staff\CreditController::class, 'document']);
    });

    Route::middleware('can:customers.verify')->group(function () {
        Route::get('/verifications', [Staff\VerificationController::class, 'index']);
        Route::post('/verifications/{record}', [Staff\VerificationController::class, 'decide']);
    });

    Route::get('/approvals', [Staff\ApprovalController::class, 'index'])->middleware('can:applications.approve');
    Route::post('/approvals/assessments/{assessment}', [Staff\ApprovalController::class, 'decideAssessment'])->middleware('can:applications.approve');
    Route::post('/approvals/applications/{application}/publish', [Staff\ApprovalController::class, 'publish'])->middleware('can:listings.publish');
    Route::post('/approvals/rules/{ruleSet}', [Staff\ApprovalController::class, 'decideRules'])->middleware('can:rules.approve');

    Route::prefix('finance')->controller(Staff\FinanceController::class)->group(function () {
        Route::middleware('can:finance.view')->group(function () {
            Route::get('/', 'overview');
            Route::get('/ledger', 'ledger');
            Route::get('/reports', 'reports');
            Route::get('/exports/{type}', 'export')->where('type', 'transactions|income|client-funds');
            Route::get('/payroll', 'payroll');
        });
        Route::middleware('can:finance.record')->group(function () {
            Route::get('/funding', 'funding');
            Route::post('/agreements/{agreement}/disbursement', 'requestDisbursement');
            Route::get('/expenses', 'expenses');
            Route::post('/expenses', 'storeExpense');
            Route::post('/ledger/{entry}/reverse', 'reverse');
            Route::post('/payroll', 'storePayroll');
        });
        Route::middleware('can:payments.reconcile')->group(function () {
            Route::post('/parties/{party}/confirm', 'confirmFunds');
            Route::get('/reconciliation', 'reconciliation');
            Route::post('/payments/{payment}/confirm', 'confirmPayment');
            Route::post('/payments/{payment}/fail', 'failPayment');
        });
        Route::middleware('can:finance.approve')->group(function () {
            Route::get('/approvals', 'checker');
            Route::post('/approvals/{approval}', 'decide');
        });
    });

    Route::prefix('sales')->controller(Staff\SalesController::class)->group(function () {
        Route::get('/', 'overview')->middleware('can:marketing.reports');
        Route::middleware('can:leads.manage')->group(function () {
            Route::get('/leads', 'leads');
            Route::post('/leads', 'storeLead');
            Route::put('/leads/{lead}', 'updateLead');
        });
        Route::middleware('can:campaigns.manage')->group(function () {
            Route::get('/campaigns', 'campaigns');
            Route::post('/campaigns', 'storeCampaign');
            Route::put('/campaigns/{campaign}', 'updateCampaign');
        });
    });

    Route::prefix('it')->controller(Staff\AdminController::class)->group(function () {
        Route::get('/', 'health')->middleware('can:system.manage');
        Route::get('/jobs', 'jobs')->middleware('can:system.manage');
        Route::post('/jobs/retry', 'retryJobs')->middleware('can:system.manage');
        Route::get('/users', 'users')->middleware('can:users.manage');
        Route::post('/users', 'storeUser')->middleware('can:users.manage');
        Route::put('/users/{user}', 'updateUser')->middleware('can:users.manage');
        Route::get('/roles', 'roles')->middleware('can:roles.manage');
        Route::put('/roles/{role}', 'updateRole')->middleware('can:roles.manage');
        Route::get('/security', 'security')->middleware('can:security.view');
    });
    Route::get('/audit', [Staff\AdminController::class, 'audit'])->middleware('can:audit.view');

    Route::get('/rules', [Staff\ConfigController::class, 'rules'])->middleware('can:rules.propose|rules.approve');
    Route::post('/rules', [Staff\ConfigController::class, 'propose'])->middleware('can:rules.propose');
    Route::get('/products', [Staff\ConfigController::class, 'products'])->middleware('can:products.manage');
    Route::put('/products/{product}', [Staff\ConfigController::class, 'updateProduct'])->middleware('can:products.manage');

    Route::prefix('executive')->middleware('can:executive.dashboard')->controller(Staff\ExecutiveController::class)->group(function () {
        Route::get('/', 'overview');
        Route::get('/risk', 'risk');
        Route::get('/people', 'people');
    });
});
