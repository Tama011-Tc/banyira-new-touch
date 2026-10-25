<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 24)->default('investor')->index();
            $t->string('status', 12)->default('active');           // active | suspended
            $t->string('locale', 5)->default('en');                 // en | ny
            $t->timestamp('mfa_confirmed_at')->nullable();          // staff MFA enrolment marker (see docs: TOTP is a documented gap)
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key', 80)->primary();
            $t->text('value');
            $t->timestamps();
        });

        Schema::create('investor_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('kyc_status', 12)->default('pending');       // pending | submitted | verified | rejected
            $t->text('national_id')->nullable();                    // encrypted cast
            $t->string('district', 60)->nullable();
            $t->string('phone', 30)->nullable();
            $t->timestamp('risk_acknowledged_at')->nullable();
            $t->bigInteger('max_per_listing')->nullable();          // personal cap, minor units
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('name');
            $t->string('display_name');
            $t->string('sector', 60);
            $t->string('district', 60);
            $t->text('description');
            $t->text('registration_no')->nullable();                // encrypted cast
            $t->unsignedTinyInteger('years_operating')->default(0);
            $t->string('contact_phone', 30)->nullable();
            $t->unsignedBigInteger('registered_by_staff_id')->nullable();
            $t->timestamp('owner_consent_at')->nullable();
            $t->string('verification_status', 12)->default('pending'); // pending | verified | rejected
            $t->timestamps();
        });

        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('status', 24)->default('draft')->index();
            $t->bigInteger('amount');
            $t->text('purpose');
            $t->unsignedSmallInteger('term_months');
            $t->string('frequency', 8)->default('monthly');
            $t->string('interest_method', 10)->default('reducing');
            $t->unsignedInteger('proposed_rate_bp');                 // annual
            $t->bigInteger('monthly_revenue')->default(0);
            $t->bigInteger('monthly_expenses')->default(0);
            $t->bigInteger('existing_debt')->default(0);
            $t->text('repayment_plan')->nullable();
            $t->text('info_request')->nullable();                    // what staff asked for
            $t->unsignedBigInteger('assisted_by')->nullable();
            $t->unsignedBigInteger('referred_by')->nullable();
            $t->unsignedInteger('terms_version')->default(1);
            $t->timestamp('submitted_at')->nullable();
            $t->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('uploaded_by');
            $t->string('type', 30);
            $t->string('original_name');
            $t->string('path');                                      // private disk only
            $t->string('mime', 80);
            $t->unsignedInteger('size');
            $t->string('sha256', 64);
            $t->timestamps();
        });

        Schema::create('credit_assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('analyst_id');
            $t->unsignedTinyInteger('score');                        // 0-100
            $t->string('risk_category', 1);                          // A-D
            $t->json('criteria');                                    // transparent breakdown
            $t->text('notes');
            $t->string('recommendation', 12);                        // approve | reject | refer | more_info
            $t->timestamps();
        });

        Schema::create('credit_decisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('decided_by');
            $t->string('decision', 14);                              // approved | rejected | info_requested
            $t->text('reason');
            $t->text('conditions')->nullable();
            $t->bigInteger('approved_amount')->nullable();
            $t->unsignedInteger('max_rate_bp')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('summary');
            $t->text('risk_disclosure');
            $t->bigInteger('funding_target');
            $t->bigInteger('min_investment');
            $t->bigInteger('max_investment');
            $t->unsignedInteger('rate_bp');                          // approved indicative annual rate
            $t->unsignedInteger('max_rate_bp');                      // ceiling for negotiation
            $t->unsignedSmallInteger('term_months');
            $t->string('frequency', 8);
            $t->string('interest_method', 10);
            $t->string('risk_category', 1);
            $t->date('assessed_on');
            $t->date('deadline');
            $t->string('status', 12)->default('draft');              // draft | reviewed | published | funded | closed
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->unsignedBigInteger('published_by')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('watchlist_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'listing_id']);
        });

        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('investor_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['listing_id', 'investor_id']);
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('sender_id');
            $t->text('body');
            $t->string('status', 14)->default('delivered');          // delivered | blocked | pending_review | released | upheld
            $t->json('flags')->nullable();
            $t->string('attachment_path')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['conversation_id', 'id']);
        });

        Schema::create('message_moderation_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('message_id')->nullable()->index();
            $t->unsignedBigInteger('conversation_id')->index();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('kind', 24);                                  // auto_block | auto_review | appeal | release | uphold | report | suspend
            $t->json('detail')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('message_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('message_id')->nullable();
            $t->unsignedBigInteger('reporter_id');
            $t->string('reason', 30);
            $t->text('detail')->nullable();
            $t->string('status', 10)->default('open');               // open | actioned | dismissed
            $t->unsignedBigInteger('handled_by')->nullable();
            $t->timestamps();
        });

        Schema::create('complaints', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('category', 30);
            $t->string('subject');
            $t->text('body');
            $t->string('status', 12)->default('open');               // open | investigating | resolved | closed
            $t->text('resolution')->nullable();
            $t->unsignedBigInteger('handled_by')->nullable();
            $t->timestamps();
        });

        Schema::create('compliance_checks', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->string('label');
            $t->text('description');
            $t->string('status', 12)->default('open');               // open | approved
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->text('evidence')->nullable();
            $t->timestamps();
        });

        Schema::create('approval_requests', function (Blueprint $t) {
            $t->id();
            $t->string('type', 40);                                  // fee_change | reversal | exception
            $t->json('payload');
            $t->text('reason');
            $t->unsignedBigInteger('requested_by');
            $t->unsignedBigInteger('decided_by')->nullable();
            $t->string('status', 10)->default('pending');            // pending | approved | rejected
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('actor_id')->nullable()->index();   // no FK on purpose: history must outlive users
            $t->string('actor_role', 24)->nullable();
            $t->string('action', 60)->index();
            $t->string('subject_type', 40)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('prev_hash', 64)->nullable();
            $t->string('hash', 64);
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        // Tamper resistance at the database layer: these tables are append-only, even for an administrator using the app's DB user.
        foreach (['audit_logs', 'message_moderation_events'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_update BEFORE UPDATE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only table'");
            DB::unprepared("CREATE TRIGGER {$table}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only table'");
        }
    }

    public function down(): void
    {
        foreach (['notifications', 'audit_logs', 'approval_requests', 'compliance_checks', 'complaints', 'message_reports', 'message_moderation_events', 'messages', 'conversations', 'watchlist_items', 'listings', 'credit_decisions', 'credit_assessments', 'application_documents', 'applications', 'businesses', 'investor_profiles', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'status', 'locale', 'mfa_confirmed_at']));
    }
};
