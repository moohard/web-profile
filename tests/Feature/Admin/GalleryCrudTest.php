<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Language;
use App\Models\User;
use Database\Factories\GalleryFactory;
use Database\Factories\GalleryImageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

function galleryAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

function galleryPayload(string $slug = 'acara-2026'): array
{
    $indonesian = Language::idFor('id');
    $english = Language::idFor('en');

    return [
        'slug' => $slug,
        'is_active' => true,
        'translations' => [
            ['language_id' => $indonesian, 'title' => 'Acara 2026', 'description' => 'Dokumentasi acara.'],
            ['language_id' => $english, 'title' => 'Event 2026', 'description' => 'Event documentation.'],
        ],
        'images' => [
            [
                'path' => 'https://cdn.test/galleries/satu.jpg',
                'captions' => [
                    ['language_id' => $indonesian, 'caption' => 'Foto satu'],
                    ['language_id' => $english, 'caption' => 'Photo one'],
                ],
            ],
            [
                'path' => 'https://cdn.test/galleries/dua.jpg',
                'captions' => [
                    ['language_id' => $indonesian, 'caption' => 'Foto dua'],
                    ['language_id' => $english, 'caption' => 'Photo two'],
                ],
            ],
        ],
    ];
}

it('renders the gallery index for authorized roles', function (): void {
    GalleryFactory::new()->withTranslation()->create();

    $this->actingAs(galleryAdmin())
        ->get('/admin/galleries')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/galleries/index')
            ->has('galleries', 1)
            ->has('languages')
        );
});

it('allows Admin and Editor, and denies Author according to GalleryPolicy', function (): void {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $gallery = GalleryFactory::new()->withTranslation()->create();

    $this->actingAs($editor)->get('/admin/galleries')->assertOk();
    $this->actingAs($editor)->post('/admin/galleries', galleryPayload('editor-gallery'))->assertRedirect();
    $this->actingAs($editor)->put("/admin/galleries/{$gallery->id}", galleryPayload('editor-gallery-update'))->assertRedirect();
    $this->actingAs($editor)->delete("/admin/galleries/{$gallery->id}")->assertRedirect();

    $gallery = GalleryFactory::new()->withTranslation()->create();

    $this->actingAs($author)->get('/admin/galleries')->assertForbidden();
    $this->actingAs($author)->post('/admin/galleries', galleryPayload('author-gallery'))->assertForbidden();
    $this->actingAs($author)->put("/admin/galleries/{$gallery->id}", galleryPayload('author-gallery-update'))->assertForbidden();
    $this->actingAs($author)->delete("/admin/galleries/{$gallery->id}")->assertForbidden();
});

it('creates a gallery with images and localized captions', function (): void {
    $indonesian = Language::idFor('id');
    $english = Language::idFor('en');

    $this->actingAs(galleryAdmin())
        ->post('/admin/galleries', galleryPayload())
        ->assertRedirect();

    $gallery = Gallery::query()->where('slug', 'acara-2026')->firstOrFail();

    expect($gallery->is_active)->toBeTrue()
        ->and($gallery->translations()->where('language_id', $indonesian)->value('title'))->toBe('Acara 2026')
        ->and($gallery->images)->toHaveCount(2)
        ->and($gallery->images[0]->path)->toBe('https://cdn.test/galleries/satu.jpg')
        ->and($gallery->images[0]->translations()->where('language_id', $indonesian)->value('caption'))->toBe('Foto satu')
        ->and($gallery->images[0]->translations()->where('language_id', $english)->value('caption'))->toBe('Photo one');
});

it('synchronizes gallery images by removing omitted images, preserving reordered images, and updating captions', function (): void {
    $indonesian = Language::idFor('id');
    $english = Language::idFor('en');
    $gallery = GalleryFactory::new()->withTranslation()->create();
    $first = GalleryImageFactory::new()->withTranslation()->create(['gallery_id' => $gallery->id, 'path' => 'https://cdn.test/galleries/first.jpg', 'sort_order' => 0]);
    $second = GalleryImageFactory::new()->withTranslation()->create(['gallery_id' => $gallery->id, 'path' => 'https://cdn.test/galleries/second.jpg', 'sort_order' => 1]);
    $payload = galleryPayload('gallery-terbarui');
    $payload['images'] = [
        [
            'id' => $second->id,
            'path' => $second->path,
            'captions' => [
                ['language_id' => $indonesian, 'caption' => 'Caption kedua baru'],
                ['language_id' => $english, 'caption' => 'Updated second caption'],
            ],
        ],
        [
            'path' => 'https://cdn.test/galleries/baru.jpg',
            'captions' => [
                ['language_id' => $indonesian, 'caption' => 'Foto baru'],
                ['language_id' => $english, 'caption' => 'New photo'],
            ],
        ],
    ];

    $this->actingAs(galleryAdmin())
        ->put("/admin/galleries/{$gallery->id}", $payload)
        ->assertRedirect();

    expect(GalleryImage::find($first->id))->toBeNull();

    $images = $gallery->fresh()->images;

    expect($images)->toHaveCount(2)
        ->and($images[0]->id)->toBe($second->id)
        ->and($images[0]->sort_order)->toBe(0)
        ->and($images[0]->translations()->where('language_id', $indonesian)->value('caption'))->toBe('Caption kedua baru')
        ->and($images[1]->path)->toBe('https://cdn.test/galleries/baru.jpg')
        ->and($images[1]->sort_order)->toBe(1);
});

it('deletes its related gallery images through the foreign-key cascade', function (): void {
    $gallery = GalleryFactory::new()->withTranslation()->create();
    $image = GalleryImageFactory::new()->withTranslation()->create(['gallery_id' => $gallery->id]);

    $this->actingAs(galleryAdmin())
        ->delete("/admin/galleries/{$gallery->id}")
        ->assertRedirect();

    expect(Gallery::find($gallery->id))->toBeNull()
        ->and(GalleryImage::find($image->id))->toBeNull();
});
