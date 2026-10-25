<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_inbound', function (Blueprint $t) {
            $t->id();
            $t->string('event_id', 80)->unique();            // the gateway's id: a replayed delivery is recognised and ignored
            $t->string('from_phone', 20)->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('text_masked', 60);                   // the code is masked before storing
            $t->string('outcome', 20);                       // accepted | declined | invalid | not_understood | needs_web | unknown_sender | rate_limited | duplicate | rejected
            $t->timestamp('created_at')->useCurrent();
            $t->index(['from_phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_inbound');
    }
};
