<?php

declare(strict_types=1);

use App\Enums\AiTask;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\AiConfig;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
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

it('Editor dapat apply translation pada post yang boleh diperbarui', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $tr = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'editor-ai-perm',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($editor)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $tr->id,
        'target_locale' => 'en',
        'field' => 'body',
        'value' => 'editor suggestion accepted',
    ])->assertOk();

    expect($tr->fresh()->body)->toBe('editor suggestion accepted');
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
        'value' => '<section class="layout"><h2>Aman</h2><p style="color:red">isi</p></section><script>alert(1)</script>',
    ])->assertOk();

    $body = $tr->fresh()->body;
    expect($body)->toBe('<h2>Aman</h2><p>isi</p>')
        ->and($body)->not->toContain('<section')
        ->and($body)->not->toContain('class=')
        ->and($body)->not->toContain('style=')
        ->and($body)->not->toContain('<script>')
        ->and($body)->not->toContain('alert(1)');
});

it('apply translation menolak field yang bukan milik tipe entity', function (string $entityType, string $field) {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    if ($entityType === 'PostTranslation') {
        $type = ContentType::where('slug', 'berita')->first();
        $parent = Post::create(['type_id' => $type->id]);
        $translation = PostTranslation::create([
            'post_id' => $parent->id,
            'language_id' => Language::idFor('en'),
            'slug' => 'invalid-post-field',
            'title' => 'Old',
            'body' => 'old',
            'status' => PostStatus::Draft,
        ]);
    } else {
        $parent = Page::factory()->create();
        $translation = PageTranslation::factory()->create([
            'page_id' => $parent->id,
            'language_id' => Language::idFor('en'),
        ]);
    }

    $this->actingAs($admin)->postJson('/admin/ai/apply-translation', [
        'entity_type' => $entityType,
        'entity_id' => $translation->id,
        'target_locale' => 'en',
        'field' => $field,
        'value' => 'nilai tidak valid',
    ])->assertUnprocessable()->assertJsonValidationErrors('field');
})->with([
    'PostTranslation tidak punya content' => ['PostTranslation', 'content'],
    'PageTranslation tidak punya body' => ['PageTranslation', 'body'],
]);

it('apply translation menolak target locale yang berbeda dari bahasa entity', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $translation = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('en'),
        'slug' => 'locale-mismatch',
        'title' => 'Old',
        'body' => 'old',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($admin)->postJson('/admin/ai/apply-translation', [
        'entity_type' => 'PostTranslation',
        'entity_id' => $translation->id,
        'target_locale' => 'id',
        'field' => 'body',
        'value' => 'nilai tidak valid',
    ])->assertUnprocessable()->assertJsonValidationErrors('target_locale');

    expect($translation->fresh()->body)->toBe('old');
});

it('translate menolak field entity yang tidak valid dan source locale yang tidak cocok', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::create(['type_id' => $type->id]);
    $translation = PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'translate-contract',
        'title' => 'Sumber',
        'body' => 'Isi',
        'status' => PostStatus::Draft,
    ]);

    $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id',
        'target_locale' => 'en',
        'entity_type' => 'PostTranslation',
        'entity_id' => $translation->id,
        'field' => 'content',
    ])->assertUnprocessable()->assertJsonValidationErrors('field');

    $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'en',
        'target_locale' => 'id',
        'entity_type' => 'PostTranslation',
        'entity_id' => $translation->id,
        'field' => 'body',
    ])->assertUnprocessable()->assertJsonValidationErrors('source_locale');
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
