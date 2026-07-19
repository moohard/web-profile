<?php

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\GalleryImageTranslation;
use App\Models\Language;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Gallery punya banyak image terurut', function () {
    $g = Gallery::create(['slug' => 'g1']);
    GalleryImage::create(['gallery_id' => $g->id, 'path' => '/a.jpg', 'sort_order' => 2]);
    GalleryImage::create(['gallery_id' => $g->id, 'path' => '/b.jpg', 'sort_order' => 1]);
    expect($g->images->first()->path)->toBe('/b.jpg');
});

it('GalleryImage translate fallback bila belum ada caption', function () {
    $g = Gallery::create(['slug' => 'g1']);
    $img = GalleryImage::create(['gallery_id' => $g->id, 'path' => '/a.jpg', 'sort_order' => 0]);
    GalleryImageTranslation::create([
        'gallery_image_id' => $img->id,
        'language_id' => Language::idFor('id'),
        'caption' => 'Caption ID',
    ]);
    $img->load('translations');
    expect($img->translate('id')?->caption)->toBe('Caption ID');
});
