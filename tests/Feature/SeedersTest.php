<?php

use App\Enums\UserRole;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\RatingCriterion;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('seed menghasilkan data lengkap', function () {
    $this->seed();

    Language::flushCache();
    expect(Language::count())->toBe(2)
        ->and(Language::where('code', 'id')->value('is_default'))->toBeTrue()
        ->and(ContentType::count())->toBe(3)
        ->and(ContentType::where('slug', 'berita')->exists())->toBeTrue()
        ->and(RatingCriterion::count())->toBe(5)
        ->and(Role::where('name', UserRole::Admin->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRole::Editor->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRole::Author->value)->exists())->toBeTrue()
        ->and(User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->exists())->toBeTrue();
});

it('Admin user memiliki role Admin', function () {
    $this->seed();

    $admin = User::where('email', env('ADMIN_EMAIL', 'admin@papenajam.test'))->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(UserRole::Admin->value))->toBeTrue();
});

it('DemoPost ter-seed dengan translation ID+EN', function () {
    $this->seed();

    $post = Post::whereHas('translations', fn ($q) => $q->where('slug', 'selamat-datang'))->first();
    expect($post)->not->toBeNull()
        ->and($post->translate('id')?->slug)->toBe('selamat-datang')
        ->and($post->translate('en')?->slug)->toBe('welcome');
});
