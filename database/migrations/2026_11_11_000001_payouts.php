<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investor_profiles', function (Blueprint $t) {
            $t->string('payout_bank', 80)->nullable();
            $t->string('payout_account_name', 120)->nullable();
            $t->text('payout_account_number')->nullable();          // encrypted cast
            $t->timestamp('payout_details_changed_at')->nullable();
        });

        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agreement_id')->constrained('loan_agreements');
            $t->unsignedBigInteger('investor_id')->index();
            $t->foreignId('repayment_id')->unique()->constrained('repayments');   // one payout per confirmed repayment, ever
            $t->bigInteger('amount');
            $t->string('status', 10)->default('pending');                          // pending | released | cancelled
            $t->unsignedBigInteger('released_by')->nullable();
            $t->timestamp('released_at')->nullable();
            $t->boolean('simulated')->default(true);
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::table('investor_profiles', fn (Blueprint $t) => $t->dropColumn(['payout_bank', 'payout_account_name', 'payout_account_number', 'payout_details_changed_at']));
    }
};
