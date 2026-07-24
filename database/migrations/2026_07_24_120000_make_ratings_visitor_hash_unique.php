<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropIndex(['visitor_hash']);
            $table->unique('visitor_hash');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropUnique(['visitor_hash']);
            $table->index('visitor_hash');
        });
    }
};
