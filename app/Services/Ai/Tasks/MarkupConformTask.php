<?php

declare(strict_types=1);

namespace App\Services\Ai\Tasks;

use App\Enums\AiTask;
use App\Services\Ai\AiClient;

class MarkupConformTask
{
    public function __construct(private AiClient $client) {}

    /**
     * Saran penyesuaian markup HTML ke referensi komponen design system.
     * Non-destruktif: hanya mengembalikan saran HTML, tidak menyimpan apa pun.
     */
    public function suggest(string $html, string $componentReference = ''): string
    {
        $reference = trim($componentReference) !== ''
            ? "Referensi komponen design system:\n{$componentReference}\n\n"
            : '';

        $prompt = $reference.
            'Sesuaikan HTML berikut agar memakai class & struktur design system. '.
            'JANGAN menambah tag <script> atau atribut on*. JANGAN mengubah teks konten. '.
            "Output HANYA HTML hasil penyesuaian, tanpa penjelasan.\n\n{$html}";

        return $this->client->task(AiTask::MarkupConform)->chat($prompt);
    }
}
