<?php

declare(strict_types=1);

use App\Enums\AiTask;
use App\Models\AiConfig;
use App\Services\Ai\ArkTranslationClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function arkConfig(bool $enabled = true): AiConfig
{
    return AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => $enabled,
        'base_url' => 'https://ark.ap-southeast.bytepluses.com/api/v3',
        'api_key' => 'ark-secret',
        'model' => 'seed-translation-250915',
        'system_prompt' => null,
    ]);
}

it('POST ke Ark /responses dengan translation_options + Bearer, lalu parse output_text', function () {
    arkConfig();

    Http::fake([
        '*/responses' => Http::response([
            'output' => [[
                'type' => 'message',
                'role' => 'assistant',
                'content' => [[
                    'type' => 'output_text',
                    'text' => 'hello world',
                ]],
            ]],
        ]),
    ]);

    $result = app(ArkTranslationClient::class)->translate('halo dunia', 'id', 'en');

    expect($result)->toBe('hello world');

    Http::assertSent(function ($request) {
        $part = $request->data()['input'][0]['content'][0];

        return str_ends_with($request->url(), '/api/v3/responses')
            && $request->hasHeader('Authorization', 'Bearer ark-secret')
            && $request->data()['model'] === 'seed-translation-250915'
            && $part['type'] === 'input_text'
            && $part['text'] === 'halo dunia'
            && $part['translation_options']['source_language'] === 'id'
            && $part['translation_options']['target_language'] === 'en';
    });
});

it('mendukung fallback field output_text tingkat atas', function () {
    arkConfig();

    Http::fake(['*/responses' => Http::response(['output_text' => 'terjemahan'])]);

    expect(app(ArkTranslationClient::class)->translate('x', 'en', 'id'))->toBe('terjemahan');
});

it('throw bila AiConfig Translation tidak aktif', function () {
    arkConfig(enabled: false);

    app(ArkTranslationClient::class)->translate('x', 'id', 'en');
})->throws(RuntimeException::class);

it('throw bila respons Ark gagal (HTTP error)', function () {
    arkConfig();
    Http::fake(['*/responses' => Http::response(['error' => 'bad request'], 400)]);

    app(ArkTranslationClient::class)->translate('x', 'id', 'en');
})->throws(RuntimeException::class);

it('throw bila hasil terjemahan kosong', function () {
    arkConfig();
    Http::fake(['*/responses' => Http::response(['output' => []])]);

    app(ArkTranslationClient::class)->translate('x', 'id', 'en');
})->throws(RuntimeException::class);
