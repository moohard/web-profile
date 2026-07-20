<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->enLang = Language::idFor('en');
    $this->type = ContentType::query()->firstOrFail();
});

function editorAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('POST /admin/posts membuat post beserta translation per bahasa yang diisi', function () {
    $category = Category::factory()->withTranslation('id', $this->idLang)->create();
    $tag = Tag::factory()->withTranslation('id', $this->idLang)->create();
    $admin = editorAdmin();

    $response = $this->actingAs($admin)->post('/admin/posts', [
        'type_id' => $this->type->id,
        'category_id' => $category->id,
        'tags' => [$tag->id],
        'featured_image' => 'https://example.test/media/1.jpg',
        'translations' => [
            [
                'language_id' => $this->idLang,
                'title' => 'Judul Post',
                'slug' => '',
                'body' => '<p>Isi konten</p>',
                'status' => 'Published',
                'published_at' => now()->toDateTimeString(),
            ],
            [
                'language_id' => $this->enLang,
                'title' => 'Post Title',
                'slug' => '',
                'body' => '<p>English body</p>',
                'status' => 'Draft',
                'published_at' => null,
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.posts.index'));

    $post = Post::query()->latest('id')->first();

    expect($post)->not->toBeNull()
        ->and($post->author_id)->toBe($admin->id)
        ->and($post->category_id)->toBe($category->id)
        ->and($post->featured_image)->toBe('https://example.test/media/1.jpg')
        ->and($post->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($post->translations()->count())->toBe(2);

    $idTranslation = PostTranslation::query()
        ->where('post_id', $post->id)
        ->where('language_id', $this->idLang)
        ->first();

    expect($idTranslation->title)->toBe('Judul Post')
        ->and($idTranslation->slug)->toBe('judul-post')
        ->and($idTranslation->status)->toBe(PostStatus::Published);
});

it('body post disanitasi sebelum disimpan', function () {
    $this->actingAs(editorAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Aman',
            'body' => '<script>alert(1)</script><p>ok</p>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $translation = PostTranslation::query()->latest('id')->first();

    expect($translation->body)->not->toContain('<script>')
        ->and($translation->body)->toBe('<p>ok</p>');
});

it('slug otomatis dibuat dari judul dan tetap unik saat bentrok dalam bahasa yang sama', function () {
    $this->actingAs(editorAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Sama Saja',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $this->actingAs(editorAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Sama Saja',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $slugs = PostTranslation::query()
        ->where('language_id', $this->idLang)
        ->where('title', 'Sama Saja')
        ->pluck('slug')
        ->all();

    expect($slugs)->toContain('sama-saja')
        ->and($slugs)->toContain('sama-saja-2');
});

it('validasi menolak bila bahasa default belum terisi judulnya', function () {
    $countBefore = Post::query()->count();

    $this->actingAs(editorAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->enLang,
            'title' => 'Only English',
            'status' => 'Draft',
        ]],
    ])->assertSessionHasErrors('translations');

    expect(Post::query()->count())->toBe($countBefore)
        ->and(PostTranslation::query()->where('title', 'Only English')->exists())->toBeFalse();
});

it('PUT /admin/posts/{post} meng-upsert translations, sync tags, dan update kategori/featured', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Lama'])->create(['type_id' => $this->type->id]);
    $category = Category::factory()->withTranslation('id', $this->idLang)->create();
    $tag = Tag::factory()->withTranslation('id', $this->idLang)->create();
    $existingTranslation = $post->translations()->first();

    $this->actingAs(editorAdmin())->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'category_id' => $category->id,
        'tags' => [$tag->id],
        'featured_image' => 'https://example.test/media/2.jpg',
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Baru',
            'slug' => $existingTranslation->slug,
            'body' => '<p>Baru</p>',
            'status' => 'Published',
            'published_at' => now()->toDateTimeString(),
        ]],
    ])->assertRedirect(route('admin.posts.index'));

    $fresh = $post->fresh();

    expect($fresh->category_id)->toBe($category->id)
        ->and($fresh->featured_image)->toBe('https://example.test/media/2.jpg')
        ->and($fresh->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($fresh->translations()->count())->toBe(1)
        ->and($fresh->translations()->first()->title)->toBe('Baru')
        ->and($fresh->translations()->first()->slug)->toBe($existingTranslation->slug);
});

it('Author boleh mengedit post miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang)->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);

    $this->actingAs($author)->get("/admin/posts/{$post->id}/edit")->assertOk();

    $this->actingAs($author)->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Update oleh Author',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    expect($post->fresh()->translations()->first()->title)->toBe('Update oleh Author');
});

it('Author tidak boleh mengedit post milik orang lain', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $other = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Milik Lain'])->create([
        'type_id' => $this->type->id,
        'author_id' => $other->id,
    ]);

    $this->actingAs($author)->get("/admin/posts/{$post->id}/edit")->assertForbidden();

    $this->actingAs($author)->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Hack',
            'status' => 'Draft',
        ]],
    ])->assertForbidden();

    expect($post->fresh()->translations()->first()->title)->toBe('Milik Lain');
});
