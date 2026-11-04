<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('body');
            $table->boolean('redacted')->default(false);      // contact details were removed
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to', 20);
            $table->string('body', 480);
            $table->string('driver');
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('area')->index();
            $table->nullableMorphs('subject');
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('guard', 10);
            $table->string('email')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('success');
            $table->string('reason')->nullable();
            $table->boolean('flagged')->default(false)->index();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['business', 'investor', 'other']);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('source');
            $table->enum('stage', ['new', 'contacted', 'qualified', 'onboarding', 'converted', 'lost'])->default('new')->index();
            $table->text('message')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable();
            $table->date('next_follow_up')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('kind');
            $table->string('note');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel');
            $table->enum('audience', ['businesses', 'investors', 'both']);
            $table->enum('status', ['draft', 'scheduled', 'running', 'complete'])->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('reach')->default(0);
            $table->decimal('budget', 15, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['campaigns', 'lead_activities', 'leads', 'system_settings', 'login_attempts', 'audit_logs', 'sms_messages', 'notifications', 'message_attachments', 'messages', 'conversation_participants', 'conversations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
