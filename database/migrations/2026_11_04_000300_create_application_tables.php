<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('business_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_product_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('term_months')->nullable();
            $table->text('purpose')->nullable();
            $table->decimal('monthly_turnover', 15, 2)->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->string('main_customers')->nullable();
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'information_required', 'assessment_in_progress',
                'approved', 'rejected', 'listed', 'fully_funded', 'closed',
            ])->default('draft')->index();
            $table->string('risk_class', 2)->nullable();
            $table->foreignId('rule_set_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->date('period');                           // first day of month
            $table->decimal('inflow', 15, 2);
            $table->decimal('outflow', 15, 2);
            $table->string('source')->default('statement');
            $table->unique(['loan_application_id', 'period']);
        });

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('information_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('message');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->enum('status', ['open', 'answered', 'cancelled'])->default('open');
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users');
            $table->foreignId('criteria_set_id')->constrained('assessment_criteria_sets');
            $table->foreignId('rule_set_id')->constrained();
            $table->json('checks')->nullable();               // verification checklist
            $table->json('risks')->nullable();                // recorded risk notes
            $table->decimal('total_score', 5, 2)->nullable();
            $table->string('suggested_class', 2)->nullable();
            $table->string('final_class', 2)->nullable();
            $table->string('override_reason')->nullable();
            $table->enum('recommendation', ['approve', 'reject'])->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('assessment_criteria');
            $table->unsignedTinyInteger('score');             // 1–5
            $table->string('note')->nullable();
            $table->unique(['assessment_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        foreach (['assessment_scores', 'assessments', 'information_requests', 'application_status_history', 'financial_records', 'application_documents', 'loan_applications'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
