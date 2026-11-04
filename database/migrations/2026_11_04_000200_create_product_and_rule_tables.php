<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('code', 1);                       // A–E
            $table->string('name');
            $table->string('summary');
            $table->text('description');
            $table->text('audience');
            $table->json('purposes');
            $table->json('eligibility');
            $table->json('requirements');
            $table->json('sectors')->nullable();
            $table->text('repayment_info');
            $table->decimal('min_amount', 15, 2);
            $table->decimal('max_amount', 15, 2);
            $table->unsignedSmallInteger('min_months');
            $table->unsignedSmallInteger('max_months');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Financing rules are versioned. Exactly one set is "active" at a time;
        // changes are proposed, approved by someone else, then take effect.
        Schema::create('rule_sets', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->date('effective_from');
            $table->enum('status', ['pending_approval', 'approved', 'active', 'superseded', 'rejected'])->index();
            $table->decimal('interest_ceiling', 5, 2);       // max annual investor rate, %
            $table->string('fee_basis')->default('principal');
            $table->string('fee_collection')->default('deducted_at_disbursement');
            $table->string('notes')->nullable();
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        // Fee tier applies when amount < upper_bound (null = no upper bound).
        Schema::create('fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_set_id')->constrained()->cascadeOnDelete();
            $table->decimal('upper_bound', 15, 2)->nullable();
            $table->decimal('rate', 5, 2);
            $table->unsignedTinyInteger('sort_order');
        });

        Schema::create('risk_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_set_id')->constrained()->cascadeOnDelete();
            $table->string('risk_class', 2);
            $table->string('label');
            $table->unsignedTinyInteger('min_score');
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedSmallInteger('max_months')->nullable();
            $table->unique(['rule_set_id', 'risk_class']);
        });

        Schema::create('assessment_criteria_sets', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->enum('status', ['draft', 'active', 'retired'])->index();
            $table->timestamps();
        });

        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_set_id')->constrained('assessment_criteria_sets')->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->unsignedTinyInteger('weight');            // weights in a set sum to 100
            $table->text('guidance');
            $table->unsignedTinyInteger('sort_order');
            $table->unique(['criteria_set_id', 'key']);
        });
    }

    public function down(): void
    {
        foreach (['assessment_criteria', 'assessment_criteria_sets', 'risk_bands', 'fee_tiers', 'rule_sets', 'loan_products'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
