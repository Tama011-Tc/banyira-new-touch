<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('invited_by')->nullable()->index());     // the sales rep who brought this business owner in
        Schema::table('applications', fn (Blueprint $t) => $t->unsignedBigInteger('assigned_to')->nullable()->index()); // the credit analyst who owns the case

        Schema::create('sales_activities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lead_id')->index();       // the business-owner user
            $t->unsignedBigInteger('staff_id')->index();      // the sales rep
            $t->string('kind', 20);                           // note | call | meeting | onboarding_session | follow_up
            $t->text('body');
            $t->date('due_on')->nullable();
            $t->timestamp('done_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
        Schema::table('applications', fn (Blueprint $t) => $t->dropColumn('assigned_to'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('invited_by'));
    }
};
