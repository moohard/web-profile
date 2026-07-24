<?php

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use Database\Factories\GalleryFactory;
use Database\Factories\GalleryImageFactory;
use Database\Factories\GalleryImageTranslationFactory;
use Database\Factories\GalleryTranslationFactory;
use Database\Factories\RatingFactory;
use Database\Factories\RatingScoreFactory;
use Database\Factories\WidgetPlacementFactory;
use Database\Factories\WidgetPlacementTargetFactory;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('Post factory withTranslation membuat post + translation', function () {
    $type = ContentType::factory()->create();
    $langId = Language::idFor('id');
    $post = Post::factory()
        ->for($type, 'type')
        ->withTranslation('id', $langId)
        ->create();
    expect($post->translations)->toHaveCount(1)
        ->and($post->translate('id'))->not->toBeNull();
});

it('ContentType factory membuat type aktif', function () {
    $t = ContentType::factory()->create();
    expect($t->is_active)->toBeTrue();
});

it('GalleryFactory membuat gallery', function () {
    $g = GalleryFactory::new()->create();
    expect($g->slug)->not->toBeEmpty()
        ->and($g->is_active)->toBeTrue();
});

it('GalleryTranslationFactory membuat translation', function () {
    $g = GalleryFactory::new()->create();
    $trans = GalleryTranslationFactory::new()->create([
        'gallery_id' => $g->id,
        'language_id' => Language::idFor('id'),
    ]);
    expect($trans->title)->not->toBeEmpty();
});

it('GalleryImageFactory membuat image', function () {
    $img = GalleryImageFactory::new()->create();
    expect($img->path)->toContain('galleries/')
        ->and($img->gallery_id)->not->toBeNull();
});

it('GalleryImageTranslationFactory membuat image translation', function () {
    $img = GalleryImageFactory::new()->create();
    $t = GalleryImageTranslationFactory::new()->create([
        'gallery_image_id' => $img->id,
        'language_id' => Language::idFor('id'),
    ]);
    expect($t)->not->toBeNull();
});

it('RatingFactory membuat rating', function () {
    $r = RatingFactory::new()->create();
    expect($r->visitor_hash)->toHaveLength(64);
});

it('RatingScoreFactory membuat score', function () {
    $score = RatingScoreFactory::new()->create();
    expect($score->score)->toBeBetween(1, 5)
        ->and($score->rating_id)->not->toBeNull()
        ->and($score->criterion_id)->not->toBeNull();
});

it('WidgetPlacementFactory membuat placement', function () {
    $p = WidgetPlacementFactory::new()->create();
    expect($p->position->value)->toBe('Sidebar')
        ->and($p->scope->value)->toBe('All');
});

it('WidgetPlacementTargetFactory membuat target', function () {
    $t = WidgetPlacementTargetFactory::new()->create();
    expect($t->target_type)->toBe('Page')
        ->and($t->target_ref)->not->toBeNull();
});
