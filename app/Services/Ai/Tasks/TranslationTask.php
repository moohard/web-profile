<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use App\Enums\AiTask;
use App\Services\Ai\AiClient;

class TranslationTask
{
    public function __construct(private AiClient $client) {}

    /**
     * Terjemahkan teks dari locale sumber ke locale target.
     * Mempertahankan tag HTML; output hanya hasil terjemahan.
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        $prompt = "Terjemahkan teks berikut dari [{$sourceLocale}] ke [{$targetLocale}]. ".
            'Pertahankan semua tag HTML apa adanya, hanya terjemahkan teks di dalamnya. '.
            "Output HANYA hasil terjemahan, tanpa penjelasan.\n\n{$text}";

        return $this->client->task(AiTask::Translation)->chat($prompt);
    }
}
