<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_placement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained('widget_placements')->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->string('target_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_placement_targets');
    }
};
