<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('to_phone', 20);
            $t->string('kind', 30);
            $t->text('body');
            $t->string('provider', 20);
            $t->boolean('simulated')->default(true);
            $t->string('status', 10)->default('sent');       // sent | failed
            $t->string('provider_ref', 40)->nullable();
            $t->string('dedupe_key', 80)->nullable()->unique(); // makes scheduled reminders idempotent
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('sms_action_tokens', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('action', 30);
            $t->string('subject_type', 30);
            $t->unsignedBigInteger('subject_id');
            $t->string('code_hash', 64);                      // the code itself is never stored
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['user_id', 'action', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_action_tokens');
        Schema::dropIfExists('sms_messages');
    }
};
