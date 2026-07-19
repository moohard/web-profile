<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('position', 30);
            $table->string('scope', 20)->default('All');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->index('widget_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_placements');
    }
};
