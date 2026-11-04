<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Marketplace ----
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('loan_application_id')->unique()->constrained()->restrictOnDelete();
            $table->string('public_name');
            $table->string('industry')->index();
            $table->string('region')->index();
            $table->text('summary');
            $table->string('purpose');
            $table->decimal('amount_requested', 15, 2);
            $table->decimal('amount_accepted', 15, 2)->default(0);
            $table->string('risk_class', 2)->index();
            $table->decimal('indicative_rate', 5, 2);
            $table->unsignedSmallInteger('term_months');
            $table->enum('status', ['open', 'fully_funded', 'closed', 'withdrawn'])->default('open')->index();
            $table->foreignId('published_by')->constrained('users');
            $table->timestamp('published_at');
            $table->timestamp('closes_at');
            $table->timestamps();
        });

        Schema::create('investment_offers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('investor_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('annual_rate', 5, 2);
            $table->unsignedSmallInteger('term_months');
            $table->text('conditions')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn', 'expired'])->default('pending')->index();
            $table->timestamp('expires_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['listing_id', 'status']);
        });

        Schema::create('offer_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_offer_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // ---- Agreements and investments ----
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('listing_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('borrower_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('rule_set_id')->constrained();
            $table->decimal('principal_total', 15, 2);
            $table->decimal('fee_rate', 5, 2);
            $table->decimal('service_fee', 15, 2);
            $table->enum('status', ['awaiting_signatures', 'awaiting_funding', 'awaiting_disbursement', 'active', 'completed', 'defaulted', 'cancelled'])->index();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agreement_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('role', ['borrower', 'investor']);
            $table->foreignId('investment_offer_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->decimal('principal', 15, 2)->nullable();
            $table->decimal('annual_rate', 5, 2)->nullable();
            $table->unsignedSmallInteger('term_months')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('funds_confirmed_at')->nullable();
            $table->unique(['agreement_id', 'user_id', 'role', 'investment_offer_id'], 'agreement_party_unique');
        });

        // An investment exists only once an agreement is signed, funded and disbursed.
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('agreement_party_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained()->restrictOnDelete();
            $table->foreignId('investor_id')->constrained('users')->restrictOnDelete();
            $table->decimal('principal', 15, 2);
            $table->decimal('annual_rate', 5, 2);
            $table->unsignedSmallInteger('term_months');
            $table->decimal('principal_repaid', 15, 2)->default(0);
            $table->decimal('interest_received', 15, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'in_arrears', 'defaulted'])->default('active')->index();
            $table->date('started_on');
            $table->timestamps();
        });

        Schema::create('repayment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date')->index();
            $table->decimal('principal_due', 15, 2);
            $table->decimal('interest_due', 15, 2);
            $table->decimal('principal_paid', 15, 2)->default(0);
            $table->decimal('interest_paid', 15, 2)->default(0);
            $table->enum('status', ['scheduled', 'partial', 'paid', 'overdue'])->default('scheduled')->index();
            $table->unique(['investment_id', 'installment_no']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->foreignId('agreement_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->enum('purpose', ['repayment', 'investor_funding', 'disbursement', 'investor_payout', 'unallocated']);
            $table->decimal('amount', 15, 2);
            $table->string('provider');
            $table->string('provider_reference')->nullable()->unique();
            $table->string('payer_msisdn', 20)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'failed', 'reversed'])->default('pending')->index();
            $table->boolean('is_demo')->default(true);
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('repayment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('repayment_schedule_id')->constrained()->restrictOnDelete();
            $table->decimal('principal', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->timestamp('created_at')->useCurrent();
        });

        // ---- Ledger: balanced, immutable, corrected only by reversal ----
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('category', [
                'banyira_cash', 'banyira_revenue', 'service_charges', 'operating_expenses',
                'client_cash', 'investor_capital', 'borrower_obligations', 'principal_repayments', 'interest_repayments',
            ])->index();
            $table->boolean('is_client_money')->default(false);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->date('entry_date')->index();
            $table->string('description');
            $table->nullableMorphs('source');
            $table->foreignId('reverses_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('memo')->nullable();
        });

        Schema::create('financial_periods', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on')->unique();
            $table->date('ends_on');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
        });

        // ---- Maker-checker approvals ----
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();                 // disbursement, expense, payroll_run, payment_reversal
            $table->nullableMorphs('subject');
            $table->json('payload');
            $table->string('summary');
            $table->decimal('amount', 15, 2)->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('incurred_on');
            $table->enum('status', ['pending_approval', 'posted', 'rejected'])->default('pending_approval');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period')->unique();
            $table->decimal('total_gross', 15, 2);
            $table->decimal('total_net', 15, 2);
            $table->enum('status', ['pending_approval', 'posted', 'rejected'])->default('pending_approval');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->decimal('gross', 15, 2);
            $table->decimal('deductions', 15, 2);
            $table->decimal('net', 15, 2);
        });
    }

    public function down(): void
    {
        foreach (['payroll_items', 'payroll_runs', 'expenses', 'approval_requests', 'financial_periods', 'journal_lines', 'journal_entries', 'ledger_accounts',
            'repayment_allocations', 'payments', 'repayment_schedules', 'investments', 'agreement_parties', 'agreements', 'offer_events', 'investment_offers', 'listings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
