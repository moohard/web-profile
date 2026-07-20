<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
});

function altAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

function altPost(): Post
{
    $type = ContentType::where('slug', 'berita')->firstOrFail();

    return Post::factory()->create(['type_id' => $type->id]);
}

it('POST /admin/media menyimpan alt sebagai custom property', function () {
    $post = altPost();

    $this->actingAs(altAdmin())->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
        'alt' => 'Foto sampul berita',
    ])->assertRedirect();

    $media = $post->fresh()->getFirstMedia('featured_image');
    expect($media->getCustomProperty('alt'))->toBe('Foto sampul berita');
});

it('PATCH /admin/media/{media} memperbarui alt + override per bahasa dan membuang override kosong', function () {
    $post = altPost();
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg'))
        ->toMediaCollection('featured_image');

    $this->actingAs(altAdmin())->patch("/admin/media/{$media->id}", [
        'alt' => 'Alt default',
        'alt_overrides' => ['en' => 'Default alt', 'id' => ''],
    ])->assertRedirect();

    $media->refresh();

    expect($media->getCustomProperty('alt'))->toBe('Alt default')
        ->and($media->getCustomProperty('alt_overrides'))->toBe(['en' => 'Default alt']);
});

it('GET /admin/media mengembalikan alt item + daftar locale', function () {
    $post = altPost();
    $post->addMedia(UploadedFile::fake()->image('x.jpg'))
        ->withCustomProperties(['alt' => 'Halo dunia'])
        ->toMediaCollection('featured_image');

    $this->actingAs(altAdmin())
        ->get('/admin/media')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/media/index')
            ->has('locales')
            ->where('media.data.0.alt', 'Halo dunia')
        );
});

it('User tanpa permission media.update ditolak PATCH alt', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');
    $post = altPost();
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg'))
        ->toMediaCollection('featured_image');

    $this->actingAs($user)
        ->patch("/admin/media/{$media->id}", ['alt' => 'x'])
        ->assertForbidden();
});
