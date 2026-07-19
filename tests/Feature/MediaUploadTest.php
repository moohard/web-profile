<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->seed();
});

it('GET /admin/media menampilkan halaman media untuk admin', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();

    $this->actingAs($admin)
        ->get('/admin/media')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/media/index')
            ->has('media.data')
        );
});

it('POST /admin/media upload sukses untuk Post', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ]);

    $response->assertRedirect();
    expect($post->fresh()->getMedia('featured_image'))->toHaveCount(1);
});

it('POST /admin/media menolak MIME tidak valid', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->create('a.txt', 100, 'text/plain'),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ]);

    $response->assertSessionHasErrors('file');
});

it('DELETE /admin/media/{media} menghapus', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg'))
        ->toMediaCollection('featured_image');

    $this->actingAs($admin)
        ->delete("/admin/media/{$media->id}")
        ->assertRedirect();

    expect(Media::find($media->id))->toBeNull();
});

it('Non-admin tidak bisa POST /admin/media', function () {
    $response = $this->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg'),
        'model_type' => 'Post',
        'model_id' => 1,
        'collection' => 'featured_image',
    ]);

    $response->assertRedirect('/login');
});
