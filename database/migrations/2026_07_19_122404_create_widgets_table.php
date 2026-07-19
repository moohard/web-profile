<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);

            // jsonb on pgsql for production; json on sqlite (phpunit :memory:)
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                $table->jsonb('config')->nullable();
            } else {
                $table->json('config')->nullable();
            }

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
