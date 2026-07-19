<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');

            // jsonb on pgsql for production; json on sqlite (phpunit :memory:)
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                $table->jsonb('content')->nullable();
            } else {
                $table->json('content')->nullable();
            }

            $table->string('hero_heading')->nullable();
            $table->string('hero_subheading')->nullable();
            $table->string('hero_cta_text')->nullable();
            $table->string('hero_cta_link')->nullable();
            $table->string('status', 20)->default('Draft');
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->unique(['page_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
