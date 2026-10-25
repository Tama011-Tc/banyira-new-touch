<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 80)->unique();
            $t->string('category', 40);
            $t->string('title_en');
            $t->text('body_en');
            $t->string('title_ny')->nullable();          // Chichewa: filled by a translator; English is shown until then
            $t->text('body_ny')->nullable();
            $t->boolean('published')->default(false);
            $t->unsignedSmallInteger('position')->default(0);
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_articles');
    }
};
