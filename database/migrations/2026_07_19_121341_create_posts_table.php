<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('content_types')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('featured_image')->nullable(); // path fallback; pakai media library utamanya
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
