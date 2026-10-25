<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExecutiveController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\ListingAdminController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', ['canRegister' => Route::has('register')]))->name('home');
Route::get('/how-it-works', fn () => Inertia::render('Risks'))->name('risks');

// Provider callbacks: authenticated by HMAC signature, never by session.
Route::post('webhooks/sms/inbound', [\App\Http\Controllers\SmsWebhookController::class, 'inbound'])->middleware('throttle:webhooks')->name('webhooks.sms');
Route::post('webhooks/payments/sandbox', [WebhookController::class, 'sandbox'])->middleware('throttle:webhooks')->name('webhooks.sandbox');

Route::middleware('auth')->group(function () {
    Route::get('password/change', [\App\Http\Controllers\Auth\ForcedPasswordController::class, 'show'])->name('password.change');
    Route::put('password/change', [\App\Http\Controllers\Auth\ForcedPasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.change.update');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');   // everyone sees their own; complaints.view sees all
    Route::post('complaints', [ComplaintController::class, 'store'])->middleware('throttle:10,1')->name('complaints.store');
    Route::put('complaints/{complaint}', [ComplaintController::class, 'update'])->middleware('can:complaints.handle')->name('complaints.update');

    // ---- Investor
    Route::middleware('can:marketplace.view')->group(function () {
        Route::get('marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
        Route::get('marketplace/{listing}', [MarketplaceController::class, 'show'])->name('marketplace.show');
    });
    Route::middleware('can:invest')->group(function () {
        Route::get('watchlist', [MarketplaceController::class, 'watchlist'])->name('watchlist.index');
        Route::post('marketplace/{listing}/watch', [MarketplaceController::class, 'toggleWatch'])->name('watchlist.toggle');
        Route::post('marketplace/{listing}/contact', [MarketplaceController::class, 'contact'])->middleware('throttle:sensitive')->name('marketplace.contact');
        Route::get('portfolio', [InvestorController::class, 'portfolio'])->name('portfolio.index');
        Route::get('portfolio/statement', [InvestorController::class, 'statement'])->name('portfolio.statement');
        Route::get('investor/profile', [InvestorController::class, 'profile'])->name('investor.profile');
        Route::post('investor/kyc', [InvestorController::class, 'submitKyc'])->middleware('throttle:sensitive')->name('investor.kyc');
        Route::post('investor/risk', [InvestorController::class, 'acknowledgeRisk'])->name('investor.risk');
    });

    // ---- Business owner
    Route::middleware('can:business.apply')->group(function () {
        Route::get('business', [BusinessController::class, 'edit'])->name('business.edit');
        Route::post('business', [BusinessController::class, 'save'])->name('business.save');
    });

    // ---- Applications: owners and the assisting sales rep (ownership is enforced inside the controller)
    Route::middleware('can:business.apply')->group(function () {
        //
    });
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])->name('index');
        Route::get('create', [ApplicationController::class, 'create'])->name('create');
        Route::post('/', [ApplicationController::class, 'store'])->name('store');
        Route::get('{application}', [ApplicationController::class, 'show'])->whereNumber('application')->name('show');
        Route::put('{application}', [ApplicationController::class, 'update'])->whereNumber('application')->name('update');
        Route::post('{application}/submit', [ApplicationController::class, 'submit'])->whereNumber('application')->middleware('throttle:sensitive')->name('submit');
        Route::post('{application}/withdraw', [ApplicationController::class, 'withdraw'])->whereNumber('application')->name('withdraw');
        Route::post('{application}/documents', [ApplicationController::class, 'upload'])->whereNumber('application')->middleware('throttle:uploads')->name('documents.store');
    });
    Route::get('documents/{document}', [ApplicationController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [ApplicationController::class, 'destroyDocument'])->name('documents.destroy');

    // ---- Sales
    Route::middleware('can:sales.workspace')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('register', [SalesController::class, 'create'])->name('create');
        Route::post('register', [SalesController::class, 'store'])->middleware('throttle:sensitive')->name('store');
        Route::post('invite', [SalesController::class, 'inviteOnly'])->middleware('throttle:sensitive')->name('invite');
        Route::get('leads/{lead}', [SalesController::class, 'show'])->whereNumber('lead')->name('lead');
        Route::post('leads/{lead}/invite', [SalesController::class, 'reinvite'])->whereNumber('lead')->middleware('throttle:sensitive')->name('reinvite');
        Route::post('leads/{lead}/business', [SalesController::class, 'addBusiness'])->whereNumber('lead')->middleware('throttle:sensitive')->name('lead.business');
        Route::post('leads/{lead}/activities', [SalesController::class, 'activity'])->whereNumber('lead')->name('activity');
        Route::post('activities/{activity}/done', [SalesController::class, 'completeActivity'])->name('activity.done');
        Route::post('applications/{application}/refer', [SalesController::class, 'refer'])->name('refer');
    });
    Route::get('learn', [\App\Http\Controllers\EducationController::class, 'index'])->name('learn.index');
    Route::get('learn/{slug}', [\App\Http\Controllers\EducationController::class, 'show'])->where('slug', '^(?!manage$)[a-z0-9\-]+$')->name('learn.show');
    Route::post('locale', [\App\Http\Controllers\EducationController::class, 'locale'])->name('locale');
    Route::middleware('can:education.manage')->group(function () {
        Route::get('learn-manage', [\App\Http\Controllers\EducationController::class, 'manage'])->name('learn.manage');
        Route::post('learn-manage', [\App\Http\Controllers\EducationController::class, 'store'])->name('learn.store');
        Route::put('learn-manage/{article}', [\App\Http\Controllers\EducationController::class, 'update'])->name('learn.update');
        Route::delete('learn-manage/{article}', [\App\Http\Controllers\EducationController::class, 'destroy'])->name('learn.destroy');
    });
    Route::get('sms-inbox', [\App\Http\Controllers\SmsController::class, 'inbox'])->name('sms.inbox');
    Route::post('sms-inbox/reply', [\App\Http\Controllers\SmsController::class, 'simulateReply'])->middleware('throttle:sensitive')->name('sms.reply');
    Route::get('sms-log', [\App\Http\Controllers\SmsController::class, 'log'])->middleware('can:sms.view')->name('sms.log');
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');

    // ---- Credit
    Route::middleware('can:credit.view')->prefix('credit')->name('credit.')->group(function () {
        Route::get('/', [CreditController::class, 'index'])->name('index');
        Route::get('{application}', [CreditController::class, 'show'])->whereNumber('application')->name('show');
        Route::post('{application}/advance', [CreditController::class, 'advance'])->middleware('can:credit.assess')->name('advance');
        Route::post('{application}/info', [CreditController::class, 'requestInfo'])->middleware('can:credit.assess')->name('info');
        Route::post('{application}/assess', [CreditController::class, 'assess'])->middleware('can:credit.assess')->name('assess');
        Route::post('{application}/decide', [CreditController::class, 'decide'])->middleware(['can:credit.approve', 'throttle:sensitive'])->name('decide');
    });
    Route::get('credit-policy', [CreditController::class, 'policy'])->middleware('can:credit.view')->name('credit.policy');
    Route::post('credit-policy/bands', [CreditController::class, 'requestBands'])->middleware(['can:credit.approve', 'throttle:sensitive'])->name('credit.bands');
    Route::post('agreements/{agreement}/default', [CreditController::class, 'markDefault'])->middleware(['can:credit.approve', 'throttle:sensitive'])->name('agreements.default');

    // ---- Listings
    Route::get('listings', [ListingAdminController::class, 'index'])->middleware('can:listing.view')->name('listings.index');
    Route::middleware('can:listing.manage')->prefix('listings')->name('listings.')->group(function () {
        Route::get('prepare/{application}', [ListingAdminController::class, 'prepare'])->name('prepare');
        Route::post('prepare/{application}', [ListingAdminController::class, 'store'])->name('store');
        Route::post('{listing}/review', [ListingAdminController::class, 'review'])->name('review');
        Route::post('{listing}/publish', [ListingAdminController::class, 'publish'])->name('publish');
    });

    // ---- Messaging & negotiation (participants only; enforced in the controller)
    Route::prefix('messages')->name('conversations.')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::get('{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation')->name('show');
        Route::post('{conversation}', [ConversationController::class, 'send'])->whereNumber('conversation')->middleware('throttle:messages')->name('send');
        Route::post('{conversation}/attach', [ConversationController::class, 'attach'])->whereNumber('conversation')->middleware('throttle:uploads')->name('attach');
        Route::post('{conversation}/report', [ConversationController::class, 'report'])->whereNumber('conversation')->middleware('throttle:sensitive')->name('report');
        Route::post('{conversation}/offers', [ConversationController::class, 'propose'])->whereNumber('conversation')->middleware('throttle:sensitive')->name('propose');
        Route::post('{conversation}/offers/{version}/accept', [ConversationController::class, 'accept'])->whereNumber('conversation')->middleware('throttle:sensitive')->name('accept');
        Route::post('{conversation}/offers/{version}/sms-code', [ConversationController::class, 'smsCode'])->whereNumber('conversation')->middleware('throttle:sensitive')->name('sms-code');
        Route::post('{conversation}/offers/{version}/withdraw', [ConversationController::class, 'withdraw'])->whereNumber('conversation')->name('withdraw');
        Route::post('{conversation}/offers/{version}/decline', [ConversationController::class, 'decline'])->whereNumber('conversation')->name('decline');
    });
    Route::post('message-items/{message}/appeal', [ConversationController::class, 'appeal'])->middleware('throttle:sensitive')->name('messages.appeal');
    Route::get('message-items/{message}/attachment', [ConversationController::class, 'attachment'])->name('messages.attachment');

    // ---- Agreements & payments
    Route::get('agreements', [AgreementController::class, 'index'])->name('agreements.index');
    Route::get('agreements/{agreement}', [AgreementController::class, 'show'])->whereNumber('agreement')->name('agreements.show');
    Route::post('agreements/{agreement}/pay', [AgreementController::class, 'pay'])->whereNumber('agreement')->middleware('throttle:sensitive')->name('agreements.pay');
    Route::get('payments/{payment}/sandbox', [AgreementController::class, 'sandbox'])->name('payments.sandbox');
    Route::post('payments/{payment}/sandbox', [AgreementController::class, 'settle'])->middleware('throttle:sensitive')->name('payments.settle');

    // ---- Executive, finance, compliance
    Route::get('executive', [ExecutiveController::class, 'index'])->middleware('can:executive.dashboard')->name('executive.index');
    Route::middleware('can:finance.workspace')->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('ledger', [FinanceController::class, 'ledger'])->name('ledger');
        Route::post('disbursements/{disbursement}/approve', [FinanceController::class, 'approveDisbursement'])->middleware(['can:finance.approve', 'throttle:sensitive'])->name('disbursement');
        Route::post('payments/{payment}/reconcile', [FinanceController::class, 'reconcile'])->middleware('can:finance.reconcile')->name('reconcile');
        Route::post('fee-schedules', [FinanceController::class, 'proposeFeeSchedule'])->middleware('can:finance.approve')->name('fee-schedules');
        Route::post('payments/{payment}/reversal', [FinanceController::class, 'requestReversal'])->middleware('can:finance.approve')->name('reversal');
        Route::post('approvals/{approval}', [FinanceController::class, 'decideApproval'])->middleware('can:finance.approve')->name('approval');
        Route::post('approvals/{approval}/execute', [FinanceController::class, 'executeReversal'])->middleware(['can:finance.approve', 'throttle:sensitive'])->name('execute');
    });
    Route::middleware('can:compliance.workspace')->prefix('compliance')->name('compliance.')->group(function () {
        Route::get('/', [ComplianceController::class, 'index'])->name('index');
        Route::post('checks/{check}', [ComplianceController::class, 'approveCheck'])->name('check');
        Route::post('live', [ComplianceController::class, 'goLive'])->middleware('can:compliance.golive')->name('live');
        Route::post('kyc/{profile}', [ComplianceController::class, 'reviewKyc'])->middleware('can:kyc.review')->name('kyc');
        Route::post('businesses/{business}', [ComplianceController::class, 'verifyBusiness'])->middleware('can:kyc.review')->name('business');
    });

    // ---- Moderation & administration
    Route::get('moderation', [ModerationController::class, 'index'])->middleware('can:moderation.view')->name('moderation.index');
    Route::middleware('can:moderation.review')->prefix('moderation')->name('moderation.')->group(function () {
        Route::post('messages/{message}', [ModerationController::class, 'resolve'])->name('resolve');
        Route::post('reports/{report}', [ModerationController::class, 'handleReport'])->name('report');
    });
    Route::middleware('can:admin.users')->prefix('admin')->name('admin.')->group(function () {
        Route::get('users', [AdminController::class, 'users'])->name('users');
        Route::post('users', [AdminController::class, 'storeStaff'])->middleware('throttle:10,1')->name('staff');
        Route::put('users/{user}/role', [AdminController::class, 'changeRole'])->name('role');
        Route::post('users/{user}/reset-password', [AdminController::class, 'resetPassword'])->middleware('throttle:10,1')->name('reset');
        Route::post('users/{user}/suspend', [AdminController::class, 'suspend'])->name('suspend');
        Route::get('settings', [AdminController::class, 'settings'])->name('settings');
        Route::put('settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('health', [AdminController::class, 'health'])->name('health');
    });
    Route::get('audit', [AdminController::class, 'audit'])->middleware('can:audit.view')->name('audit.index');
});

require __DIR__.'/auth.php';
