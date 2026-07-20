<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use App\Enums\AiTask;
use App\Services\Ai\AiClient;

class ContentRefinementTask
{
    public function __construct(private AiClient $client) {}

    /**
     * Saran penyempurnaan konten mengikuti gaya penulisan (writing style).
     * Non-destruktif: hanya mengembalikan saran teks; pertahankan tag HTML.
     */
    public function suggest(string $text, string $writingStylePrompt): string
    {
        $style = trim($writingStylePrompt) !== ''
            ? "Gaya penulisan yang diinginkan: {$writingStylePrompt}\n\n"
            : '';

        $prompt = $style.
            'Perbaiki dan sempurnakan teks berikut agar lebih jelas, rapi, dan enak dibaca. '.
            'Pertahankan makna dan semua tag HTML apa adanya, hanya sempurnakan teksnya. '.
            "Output HANYA teks hasil perbaikan, tanpa penjelasan.\n\n{$text}";

        return $this->client->task(AiTask::ContentRefinement)->chat($prompt);
    }
}
