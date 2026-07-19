<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->text('comment')->nullable();
            $table->string('visitor_hash', 64);
            $table->timestamps();
            $table->index('visitor_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
