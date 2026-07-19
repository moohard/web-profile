<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unique(['content_type_id', 'language_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_type_translations');
    }
};
