<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unique(['tag_id', 'language_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
