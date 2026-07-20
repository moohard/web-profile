<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiTask;
use App\Models\AiConfig;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien terjemahan BytePlus Ark (model seed-translation) via Responses API.
 *
 * Berbeda dari provider chat (AiClient): model ini memakai `translation_options`
 * (source_language/target_language) pada endpoint `/responses`, bukan prompt di
 * `/chat/completions`. Konfigurasi (base_url, api_key, model) dari AiConfig task Translation.
 */
class ArkTranslationClient
{
    private const DEFAULT_BASE_URL = 'https://ark.ap-southeast.bytepluses.com/api/v3';

    private const DEFAULT_MODEL = 'seed-translation-250915';

    /**
     * Terjemahkan teks dari source locale ke target locale.
     * Locale memakai kode 2-huruf (id, en, ...) yang didukung seed-translation.
     */
    public function translate(string $text, string $sourceLocale, string $targetLocale): string
    {
        $config = AiConfig::resolve(AiTask::Translation)
            ?? throw new RuntimeException('AI config untuk task [Translation] tidak diaktifkan.');

        $baseUrl = rtrim($config->base_url ?: self::DEFAULT_BASE_URL, '/');
        $model = $config->model ?: self::DEFAULT_MODEL;

        $response = Http::withToken((string) $config->api_key)
            ->acceptJson()
            ->asJson()
            ->timeout(60)
            ->post($baseUrl.'/responses', [
                'model' => $model,
                'input' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $text,
                        'translation_options' => [
                            'source_language' => $sourceLocale,
                            'target_language' => $targetLocale,
                        ],
                    ]],
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Ark translation gagal: HTTP '.$response->status());
        }

        $translated = $this->extractText($response->json());

        if ($translated === '') {
            throw new RuntimeException('Ark translation mengembalikan teks kosong.');
        }

        return $translated;
    }

    /**
     * Ambil teks hasil dari struktur Responses API:
     * `output[].content[]` (type `output_text`) → `text`; fallback `output_text` tingkat atas.
     */
    private function extractText(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        if (isset($payload['output_text']) && is_string($payload['output_text'])) {
            return trim($payload['output_text']);
        }

        $parts = [];

        foreach ($payload['output'] ?? [] as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text']) && is_string($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }

        return trim(implode('', $parts));
    }
}
