<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_assessments', fn (Blueprint $t) => $t->string('risk_category', 4)->change());
        Schema::table('listings', fn (Blueprint $t) => $t->string('risk_category', 4)->change());
    }

    public function down(): void
    {
        Schema::table('credit_assessments', fn (Blueprint $t) => $t->string('risk_category', 1)->change());
        Schema::table('listings', fn (Blueprint $t) => $t->string('risk_category', 1)->change());
    }
};
