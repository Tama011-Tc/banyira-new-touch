<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('group');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('trading_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('business_type')->nullable();
            $table->string('industry')->index();
            $table->string('region')->nullable()->index();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('years_operating')->nullable();
            $table->unsignedInteger('employees')->nullable();
            $table->decimal('monthly_turnover', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('payout_method')->nullable();
            $table->text('payout_account')->nullable();    // encrypted cast
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('investor_type')->default('Individual');
            $table->text('national_id')->nullable();       // encrypted cast
            $table->string('source_of_funds')->nullable();
            $table->string('payout_method')->nullable();
            $table->text('payout_account')->nullable();    // encrypted cast
            $table->json('preferences')->nullable();
            $table->timestamp('risk_acknowledged_at')->nullable();
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('staff_number')->unique();
            $table->string('department');
            $table->string('job_title');
            $table->timestamps();
        });

        Schema::create('verification_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['email', 'phone', 'identity', 'business_registration', 'bank_account', 'source_of_funds']);
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('provider')->default('manual');
            $table->string('reference')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'kind']);
        });

        Schema::create('phone_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['phone_verification_codes', 'verification_records', 'employee_profiles', 'investor_profiles', 'business_profiles', 'role_user', 'permission_role', 'permissions', 'roles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
