<?php

declare(strict_types=1);

use App\Enums\UserRole;
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

it('POST /admin/media menolak collection di luar allowlist', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'secret_gallery',
    ]);

    $response->assertSessionHasErrors('collection');
    expect($post->fresh()->getMedia('secret_gallery'))->toHaveCount(0);
});

it('POST /admin/media menolak SVG untuk mencegah XSS berbasis file', function () {
    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);

    $response = $this->actingAs($admin)->post('/admin/media', [
        'file' => UploadedFile::fake()->createWithContent(
            'x.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        ),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ]);

    $response->assertSessionHasErrors('file');
    expect($post->fresh()->getMedia('featured_image'))->toHaveCount(0);
});

it('User tanpa media.create mendapat 403 pada upload', function () {
    // Hanya access-admin — tanpa permission media.* dari role
    $user = User::factory()->create()->givePermissionTo('access-admin');

    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id]);

    $this->actingAs($user)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ])->assertForbidden();
});

it('Author ditolak upload media ke post yang bukan miliknya', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $type = ContentType::where('slug', 'berita')->first();
    // Post tanpa author_id → bukan milik $author → PostPolicy::update = false
    $post = Post::factory()->create(['type_id' => $type->id]);

    $this->actingAs($author)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ])->assertForbidden();
});

it('Author boleh upload media ke post miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $type = ContentType::where('slug', 'berita')->first();
    $post = Post::factory()->create(['type_id' => $type->id, 'author_id' => $author->id]);

    $response = $this->actingAs($author)->post('/admin/media', [
        'file' => UploadedFile::fake()->image('a.jpg', 800, 600),
        'model_type' => 'Post',
        'model_id' => $post->id,
        'collection' => 'featured_image',
    ]);

    $response->assertRedirect();
    expect($post->fresh()->getMedia('featured_image'))->toHaveCount(1);
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
