<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use App\Services\Ai\ArkTranslationClient;

class TranslationTask
{
    public function __construct(private ArkTranslationClient $client) {}

    /**
     * Terjemahkan teks dari locale sumber ke locale target via BytePlus Ark
     * seed-translation (translation_options), bukan prompt chat. Output hanya
     * hasil terjemahan.
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        return $this->client->translate($text, $sourceLocale, $targetLocale);
    }
}
