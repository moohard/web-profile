<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AiTask;
use App\Models\AiConfig;
use Illuminate\Database\Seeder;

class AiConfigSeeder extends Seeder
{
    /**
     * Konfigurasi provider AI per-tugas. TRANSLATION memakai BytePlus Ark
     * seed-translation (Responses API + translation_options). Diaktifkan hanya
     * bila ARK_API_KEY tersedia agar tak ada config "enabled tanpa key".
     */
    public function run(): void
    {
        $arkKey = (string) config('services.ark.key', '');

        // Tanpa API key tidak ada gunanya menyeed config (dan menghindari
        // "enabled tanpa key"). Admin isi via Pengaturan bila key belum ada.
        if ($arkKey === '') {
            return;
        }

        AiConfig::updateOrCreate(
            ['task' => AiTask::Translation],
            [
                'base_url' => (string) config('services.ark.base_url'),
                'model' => (string) config('services.ark.translation_model'),
                'api_key' => $arkKey,
                'system_prompt' => null,
                'enabled' => true,
            ],
        );
    }
}
