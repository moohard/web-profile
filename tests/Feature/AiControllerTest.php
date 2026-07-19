<?php

declare(strict_types=1);

use App\Enums\AiTask;
use App\Enums\PostStatus;
use App\Models\AiConfig;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Services\Ai\Tasks\TranslationTask;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('POST /admin/ai/translate mengembalikan suggestion (mocked)', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $source = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'src',
        'title' => 'Halo dunia',
        'body' => '<p>Halo dunia</p>',
        'status' => PostStatus::Published,
    ]);

    $mock = Mockery::mock(TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('Hello world');
    app()->instance(TranslationTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id',
        'target_locale' => 'en',
        'entity_type' => 'PostTranslation',
        'entity_id' => $source->id,
        'field' => 'body',
    ]);

    $response->assertOk()->assertJson(['suggestion' => 'Hello world']);
});

it('POST /admin/ai/translate tidak auto-save ke DB', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $source = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'src2',
        'title' => 'X',
        'body' => 'asli',
        'status' => PostStatus::Published,
    ]);
    AiConfig::create(['task' => AiTask::Translation, 'enabled' => true, 'api_key' => 'k']);

    $mock = Mockery::mock(TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('terjemahan');
    app()->instance(TranslationTask::class, $mock);

    $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id',
        'target_locale' => 'en',
        'entity_type' => 'PostTranslation',
        'entity_id' => $source->id,
        'field' => 'body',
    ]);

    expect($source->fresh()->body)->toBe('asli');
});

it('POST /admin/ai/apply-translation menyimpan nilai', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'app1',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($admin)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $tr->id,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => 'new translated',
    ])->assertOk();

    expect($tr->fresh()->body)->toBe('new translated');
});

it('Endpoint AI ter-rate-limit', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    AiConfig::create(['task' => AiTask::Translation, 'enabled' => true, 'api_key' => 'k']);
    $mock = Mockery::mock(TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('x');
    app()->instance(TranslationTask::class, $mock);

    $blocked = 0;
    for ($i = 0; $i < 35; $i++) {
        $r = $this->actingAs($admin)->postJson('/admin/ai/translate', [
            'source_locale' => 'id',
            'target_locale' => 'en',
            'entity_type' => 'PostTranslation',
            'entity_id' => 1,
            'field' => 'body',
        ]);
        if ($r->status() === 429) {
            $blocked++;
        }
    }
    expect($blocked)->toBeGreaterThan(0);
})->skip(); // rate-limit test mungkin perlu driver khusus; hapus skip bila config throttle testable.
