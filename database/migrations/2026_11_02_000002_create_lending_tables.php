<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('status', 12)->default('open');               // open | accepted | declined | withdrawn
            $t->timestamps();
        });

        Schema::create('offer_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->unsignedBigInteger('proposed_by');
            $t->bigInteger('principal');
            $t->unsignedInteger('rate_bp');
            $t->unsignedSmallInteger('term_months');
            $t->string('frequency', 8);
            $t->string('interest_method', 10);
            $t->unsignedTinyInteger('grace_instalments')->default(0);
            $t->json('fees');                                        // disclosed fee snapshot for this version
            $t->string('content_hash', 64);
            $t->string('status', 12)->default('proposed');           // proposed | superseded | declined | accepted
            $t->timestamp('investor_accepted_at')->nullable();
            $t->timestamp('business_accepted_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->unique(['offer_id', 'version']);
        });

        Schema::create('loan_agreements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('offer_version_id')->unique()->constrained('offer_versions');
            $t->foreignId('listing_id')->constrained();
            $t->foreignId('application_id')->constrained();
            $t->foreignId('investor_id')->constrained('users');
            $t->foreignId('business_id')->constrained();
            $t->bigInteger('principal');
            $t->unsignedInteger('rate_bp');
            $t->unsignedSmallInteger('term_months');
            $t->string('frequency', 8);
            $t->string('interest_method', 10);
            $t->unsignedTinyInteger('grace_instalments')->default(0);
            $t->bigInteger('total_interest');
            $t->bigInteger('total_payable');
            $t->json('fees');
            $t->string('status', 16)->default('pending_funding');    // pending_funding | funded | active | overdue | restructured | defaulted | completed | cancelled
            $t->bigInteger('unapplied')->default(0);                 // overpayments held until applied to later instalments
            $t->boolean('simulated')->default(true);
            $t->string('agreement_hash', 64);
            $t->timestamp('funded_at')->nullable();
            $t->timestamp('disbursed_at')->nullable();
            $t->date('first_due_on')->nullable();
            $t->timestamps();
        });

        Schema::create('investment_commitments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->unique()->constrained('loan_agreements')->cascadeOnDelete();
            $t->foreignId('investor_id')->constrained('users');
            $t->bigInteger('amount');
            $t->string('status', 10)->default('pledged');            // pledged | funded | cancelled
            $t->timestamp('risk_acknowledged_at');
            $t->timestamps();
        });

        Schema::create('repayment_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->constrained('loan_agreements')->cascadeOnDelete();
            $t->unsignedSmallInteger('number');
            $t->date('due_on');
            $t->bigInteger('principal_due');
            $t->bigInteger('interest_due');
            $t->bigInteger('principal_paid')->default(0);
            $t->bigInteger('interest_paid')->default(0);
            $t->string('status', 10)->default('scheduled');           // scheduled | partial | paid | overdue
            $t->timestamps();
            $t->unique(['agreement_id', 'number']);
        });

        Schema::create('payment_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('agreement_id')->nullable()->constrained('loan_agreements');
            $t->string('purpose', 24);                                // funding | repayment
            $t->bigInteger('amount');
            $t->string('idempotency_key', 64)->unique();
            $t->string('provider', 20)->default('sandbox');
            $t->string('provider_ref', 64)->nullable()->unique();
            $t->string('status', 12)->default('initiated');           // initiated | succeeded | failed | reversed
            $t->boolean('simulated')->default(true);
            $t->string('failure_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('sandbox_transactions', function (Blueprint $t) {   // the simulated provider's own books
            $t->id();
            $t->string('ref', 64)->unique();
            $t->bigInteger('amount');
            $t->string('status', 12)->default('pending');             // pending | succeeded | failed
            $t->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 20);
            $t->string('event_id', 64);
            $t->boolean('signature_valid');
            $t->json('payload');
            $t->string('outcome', 24)->nullable();                    // processed | duplicate | rejected | unknown_payment
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['provider', 'event_id']);
        });

        Schema::create('repayments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->constrained('loan_agreements');
            $t->foreignId('payment_attempt_id')->unique()->constrained('payment_attempts');
            $t->bigInteger('amount');
            $t->bigInteger('applied_interest')->default(0);
            $t->bigInteger('applied_principal')->default(0);
            $t->bigInteger('unapplied')->default(0);                  // overpayment held, never lost
            $t->timestamp('received_at')->useCurrent();
            $t->timestamp('reversed_at')->nullable();
        });

        Schema::create('repayment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('repayment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('schedule_id')->constrained('repayment_schedules');
            $t->bigInteger('interest');
            $t->bigInteger('principal');
        });

        Schema::create('disbursements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->unique()->constrained('loan_agreements');
            $t->bigInteger('gross');
            $t->bigInteger('fees');
            $t->bigInteger('net');
            $t->string('status', 18)->default('pending_approval');    // pending_approval | simulated (released) | cancelled
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('platform_fees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->constrained('loan_agreements');
            $t->string('fee_type', 24);                               // origination | investor_service | processing
            $t->string('payer', 10);                                  // borrower | investor | platform
            $t->bigInteger('basis_amount');
            $t->unsignedInteger('rate_bp')->default(0);
            $t->bigInteger('fixed_amount')->default(0);
            $t->bigInteger('amount');
            $t->bigInteger('tax')->default(0);
            $t->bigInteger('net');
            $t->string('status', 10)->default('accrued');             // accrued | collected | refunded
            $t->string('reconciliation_ref', 64)->nullable();
            $t->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->uuid('entry_group')->index();
            $t->string('account', 32)->index();
            $t->string('direction', 6);                                // debit | credit
            $t->bigInteger('amount');
            $t->string('currency', 3)->default('MWK');
            $t->string('ref_type', 30)->nullable();
            $t->unsignedBigInteger('ref_id')->nullable();
            $t->string('memo')->nullable();
            $t->boolean('simulated')->default(true);
            $t->uuid('reverses_group')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['ref_type', 'ref_id']);
        });

        Schema::create('reconciliation_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_attempt_id')->constrained();
            $t->string('status', 10);                                  // matched | mismatch
            $t->bigInteger('provider_amount');
            $t->bigInteger('ledger_amount');
            $t->unsignedBigInteger('checked_by')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        DB::unprepared("CREATE TRIGGER ledger_entries_no_update BEFORE UPDATE ON ledger_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ledger is append-only'");
        DB::unprepared("CREATE TRIGGER ledger_entries_no_delete BEFORE DELETE ON ledger_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ledger is append-only'");
    }

    public function down(): void
    {
        foreach (['reconciliation_records', 'ledger_entries', 'platform_fees', 'disbursements', 'repayment_allocations', 'repayments', 'webhook_events', 'sandbox_transactions', 'payment_attempts', 'repayment_schedules', 'investment_commitments', 'loan_agreements', 'offer_versions', 'offers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
