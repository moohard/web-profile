<?php

use App\Enums\AiTask;
use App\Models\AiConfig;
use App\Services\Ai\AiClient;
use App\Services\Ai\ArkTranslationClient;
use App\Services\Ai\Tasks\ContentRefinementTask;
use App\Services\Ai\Tasks\MarkupConformTask;
use App\Services\Ai\Tasks\TranslationTask;
use Illuminate\Support\Facades\DB;

it('AiConfig api_key tersimpan terenkripsi', function () {
    $cfg = AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => true,
        'base_url' => 'https://api.example.com/v1',
        'api_key' => 'super-secret',
        'model' => 'gpt-4o-mini',
        'system_prompt' => 'You are a translator.',
    ]);

    $raw = DB::table('ai_configs')->where('id', $cfg->id)->value('api_key');

    expect($raw)->not->toBe('super-secret')
        ->and($cfg->fresh()->api_key)->toBe('super-secret');
});

it('TranslationTask mendelegasikan ke ArkTranslationClient', function () {
    $mock = Mockery::mock(ArkTranslationClient::class);
    $mock->shouldReceive('translate')
        ->once()
        ->with('halo dunia', 'id', 'en')
        ->andReturn('hello world');

    $task = new TranslationTask($mock);

    expect($task->translate('halo dunia', 'id', 'en'))->toBe('hello world');
});

it('ContentRefinementTask::suggest throw NotImplementedException', function () {
    app(ContentRefinementTask::class)->suggest('text', 'style');
})->throws(RuntimeException::class);

it('MarkupConformTask::suggest throw NotImplementedException', function () {
    app(MarkupConformTask::class)->suggest('<html>', 'ref');
})->throws(RuntimeException::class);

it('AiClient::task throw jika AiConfig tidak diaktifkan', function () {
    app(AiClient::class)->task(AiTask::ContentRefinement);
})->throws(RuntimeException::class);
