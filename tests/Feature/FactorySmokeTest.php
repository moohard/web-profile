<?php

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;

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
