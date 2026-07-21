<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

function tagAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/tags menampilkan daftar tag untuk admin', function () {
    $idLang = Language::idFor('id');
    Tag::factory()->withTranslation('id', $idLang, ['name' => 'Populer'])->create();

    $this->actingAs(tagAdmin())
        ->get('/admin/tags')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/tags/index')
            ->has('tags', 1)
            ->has('languages')
        );
});

it('POST /admin/tags membuat tag beserta translation per bahasa', function () {
    $idLang = Language::idFor('id');
    $enLang = Language::idFor('en');

    $this->actingAs(tagAdmin())
        ->post('/admin/tags', [
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Wisata'],
                ['language_id' => $enLang, 'name' => 'Tourism'],
            ],
        ])
        ->assertRedirect();

    $tag = Tag::query()->where('slug', 'wisata')->first();

    expect($tag)->not->toBeNull()
        ->and($tag->translations()->count())->toBe(2)
        ->and(TagTranslation::query()->where('tag_id', $tag->id)->where('language_id', $idLang)->value('name'))->toBe('Wisata')
        ->and(TagTranslation::query()->where('tag_id', $tag->id)->where('language_id', $enLang)->value('name'))->toBe('Tourism');
});

it('PUT /admin/tags/{tag} memperbarui tag dan translation', function () {
    $idLang = Language::idFor('id');
    $tag = Tag::factory()->withTranslation('id', $idLang, ['name' => 'Lama'])->create(['slug' => 'lama']);

    $this->actingAs(tagAdmin())
        ->put("/admin/tags/{$tag->id}", [
            'slug' => 'baru',
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Baru'],
            ],
        ])
        ->assertRedirect();

    $fresh = $tag->fresh();

    expect($fresh->slug)->toBe('baru')
        ->and($fresh->translations()->where('language_id', $idLang)->value('name'))->toBe('Baru');
});

it('DELETE ditolak bila tag masih terhubung ke post', function () {
    $idLang = Language::idFor('id');
    $tag = Tag::factory()->withTranslation('id', $idLang)->create();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);
    $post->tags()->attach($tag->id);

    $this->actingAs(tagAdmin())
        ->delete("/admin/tags/{$tag->id}")
        ->assertRedirect();

    expect(Tag::find($tag->id))->not->toBeNull();
});

it('DELETE ditolak bila tag masih terhubung ke post yang sudah di-trash (soft-deleted)', function () {
    // Post trashed pun MENGUNCI penghapusan tag (sampai di-forceDelete) demi
    // menjaga garansi restore — lihat catatan di ContentTypeCrudTest.
    $idLang = Language::idFor('id');
    $tag = Tag::factory()->withTranslation('id', $idLang)->create();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);
    $post->tags()->attach($tag->id);
    $post->delete();

    $this->actingAs(tagAdmin())
        ->delete("/admin/tags/{$tag->id}")
        ->assertRedirect();

    expect(Tag::find($tag->id))->not->toBeNull();
});

it('DELETE menghapus tag tanpa post terkait', function () {
    $idLang = Language::idFor('id');
    $tag = Tag::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs(tagAdmin())
        ->delete("/admin/tags/{$tag->id}")
        ->assertRedirect();

    expect(Tag::find($tag->id))->toBeNull();
});

it('User tanpa content-types.viewAny mendapat 403', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');
    $idLang = Language::idFor('id');
    $tag = Tag::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs($user)->get('/admin/tags')->assertForbidden();
    $this->actingAs($user)->post('/admin/tags', [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->put("/admin/tags/{$tag->id}", [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->delete("/admin/tags/{$tag->id}")->assertForbidden();
});
