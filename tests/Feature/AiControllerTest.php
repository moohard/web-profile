<?php

declare(strict_types=1);

use App\Enums\AiTask;
use App\Enums\PostStatus;
use App\Enums\UserRole;
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

it('Author tidak boleh apply translation ke post (policy deny → 403)', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'auth-deny',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    // Author tidak punya ai.update → 403 dari middleware permission
    $this->actingAs($author)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $tr->id,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => 'hijack',
    ])->assertForbidden();

    expect($tr->fresh()->body)->toBe('old');
});

it('User tanpa permission ai.update mendapat 403 pada apply-translation', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'no-ai-perm',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    // Editor boleh update post, tetapi seeder tidak memberi ai.* → 403
    $this->actingAs($editor)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $tr->id,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => 'should not save',
    ])->assertForbidden();

    expect($tr->fresh()->body)->toBe('old');
});

it('Admin apply body men-sanitize script XSS', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'sanitize-body',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($admin)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $tr->id,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => '<p>aman</p><script>alert(1)</script>',
    ])->assertOk();

    $body = $tr->fresh()->body;
    expect($body)->toContain('<p>aman</p>')
        ->and($body)->not->toContain('<script>')
        ->and($body)->not->toContain('alert(1)');
});

it('Guest tidak bisa akses endpoint AI', function () {
    $this->postJson('/admin/ai/translate', [
        'source_locale' => 'id',
        'target_locale' => 'en',
        'entity_type' => 'PostTranslation',
        'entity_id' => 1,
        'field' => 'body',
    ])->assertUnauthorized();

    $this->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => 1,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => 'x',
    ])->assertUnauthorized();
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
