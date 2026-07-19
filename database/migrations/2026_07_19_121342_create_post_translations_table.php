<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('Draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->unique(['post_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
            $table->index('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_translations');
    }
};
