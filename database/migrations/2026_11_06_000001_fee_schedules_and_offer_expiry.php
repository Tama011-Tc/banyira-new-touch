<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->string('status', 10)->default('approved');        // approved | retired (drafts live in approval_requests)
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->unsignedInteger('tax_bp')->default(0);
            $t->unsignedInteger('processing_bp')->default(0);       // charged by the payment provider; borne by the platform
            $t->json('tiers');                                      // by principal: [{min,max|null,origination_bp,origination_fixed,investor_bp,investor_fixed}]
            $t->unsignedBigInteger('requested_by')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });
        Schema::table('offer_versions', fn (Blueprint $t) => $t->timestamp('expires_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('offer_versions', fn (Blueprint $t) => $t->dropColumn('expires_at'));
        Schema::dropIfExists('fee_schedules');
    }
};
