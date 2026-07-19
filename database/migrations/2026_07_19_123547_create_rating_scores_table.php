<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rating_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('rating_criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unique(['rating_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_scores');
    }
};
