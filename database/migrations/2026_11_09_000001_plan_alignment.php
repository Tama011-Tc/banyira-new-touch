<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business plan: target SMEs with at least 6 months of financial records and 1 year of trading.
        Schema::table('applications', fn (Blueprint $t) => $t->unsignedSmallInteger('records_months')->default(0));
        Schema::table('credit_decisions', fn (Blueprint $t) => $t->text('exception_reason')->nullable());   // approving outside the usual criteria needs a written reason
    }

    public function down(): void
    {
        Schema::table('credit_decisions', fn (Blueprint $t) => $t->dropColumn('exception_reason'));
        Schema::table('applications', fn (Blueprint $t) => $t->dropColumn('records_months'));
    }
};
