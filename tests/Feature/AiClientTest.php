<?php

use App\Enums\AiTask;
use App\Models\AiConfig;
use App\Services\Ai\AiClient;
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

it('TranslationTask::translate memanggil AiClient dengan prompt', function () {
    AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => true,
        'api_key' => 'k',
        'base_url' => 'https://x.test/v1',
        'model' => 'gpt-4o-mini',
        'system_prompt' => '',
    ]);

    $mock = Mockery::mock(AiClient::class);
    $mock->shouldReceive('task')->with(AiTask::Translation)->andReturnSelf();
    $mock->shouldReceive('chat')
        ->with(Mockery::on(fn ($p) => str_contains($p, 'halo dunia')
            && str_contains($p, '[id]')
            && str_contains($p, '[en]')))
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
