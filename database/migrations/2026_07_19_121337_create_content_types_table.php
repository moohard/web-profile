<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->foreignId('writing_style_id')->nullable()->constrained()->nullOnDelete();
            $table->string('archive_template_key')->default('default');
            $table->string('single_template_key')->default('default');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_types');
    }
};
