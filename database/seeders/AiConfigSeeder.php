<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AiTask;
use App\Models\AiConfig;
use Illuminate\Database\Seeder;

class AiConfigSeeder extends Seeder
{
    /**
     * Bootstrap konfigurasi provider AI per-tugas dari env — HANYA sebagai nilai
     * awal. Sumber kebenaran adalah tabel ai_configs yang dikelola admin lewat
     * Pengaturan → Konfigurasi AI. Memakai firstOrCreate agar TIDAK menimpa
     * konfigurasi yang sudah diedit admin saat re-seed.
     *
     * - TRANSLATION       → BytePlus Ark seed-translation (Responses API).
     * - CONTENT_REFINEMENT → MegaNova (chat OpenAI-compatible).
     */
    public function run(): void
    {
        $this->bootstrapTask(
            AiTask::Translation,
            (string) config('services.ark.key', ''),
            (string) config('services.ark.base_url', ''),
            (string) config('services.ark.translation_model', ''),
        );

        $this->bootstrapTask(
            AiTask::ContentRefinement,
            (string) config('services.meganova.key', ''),
            (string) config('services.meganova.base_url', ''),
            (string) config('services.meganova.chat_model', ''),
        );
    }

    private function bootstrapTask(AiTask $task, string $key, string $baseUrl, string $model): void
    {
        if ($key === '') {
            return;
        }

        AiConfig::firstOrCreate(
            ['task' => $task],
            [
                'base_url' => $baseUrl,
                'model' => $model,
                'api_key' => $key,
                'system_prompt' => null,
                'enabled' => true,
            ],
        );
    }
}
