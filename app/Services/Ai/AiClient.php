<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\AiTask;
use App\Models\AiConfig;
use Laravel\Ai\Ai;
use Laravel\Ai\Messages\UserMessage;
use RuntimeException;

class AiClient
{
    private ?AiConfig $config = null;

    /**
     * Atur task AI aktif dan resolusi konfigurasi dari database.
     */
    public function task(AiTask $task): self
    {
        $this->config = AiConfig::resolve($task)
            ?? throw new RuntimeException("AI config untuk task [{$task->value}] tidak diaktifkan.");

        return $this;
    }

    /**
     * Kirim pesan chat ke provider AI sesuai AiConfig task aktif.
     */
    public function chat(string $userMessage): string
    {
        if ($this->config === null) {
            throw new RuntimeException('Task AI belum dipilih. Panggil task() terlebih dahulu.');
        }

        $this->applyRuntimeConfig();

        $provider = Ai::textProvider('openai');
        $model = $this->config->model ?? 'gpt-4o-mini';
        $instructions = $this->config->system_prompt ?? '';

        // Laravel AI SDK: generate multi-step lewat TextGenerationLoop (bukan generateText).
        $response = $provider->textGenerationLoop()->generate(
            $provider,
            $model,
            $instructions,
            [new UserMessage($userMessage)],
            [],
            null,
            null,
            60,
        );

        return $response->text;
    }

    /**
     * Override key + URL OpenAI runtime dari AiConfig, lalu purge cache provider.
     */
    private function applyRuntimeConfig(): void
    {
        config([
            'ai.providers.openai.key' => $this->config->api_key ?? config('ai.providers.openai.key'),
            'ai.providers.openai.url' => $this->config->base_url ?? config('ai.providers.openai.url'),
            'ai.default' => 'openai',
        ]);

        // Instance provider di-cache; purge agar config baru terbaca.
        Ai::forgetInstance('openai');
    }
}
