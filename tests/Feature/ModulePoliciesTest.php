<?php

use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Gallery;
use App\Models\Menu;
use App\Models\RatingCriterion;
use App\Models\User;
use App\Models\Widget;
use App\Policies\ContactMessagePolicy;
use App\Policies\GalleryPolicy;
use App\Policies\MenuPolicy;
use App\Policies\RatingCriterionPolicy;
use App\Policies\WidgetPolicy;

beforeEach(function () {
    $this->seed();
});

it('menerapkan matrix permission Menu untuk setiap peran', function (UserRole $role, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);
    $policy = new MenuPolicy;
    $menu = new Menu;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user, $menu))->toBe($expected)
        ->and($policy->create($user))->toBe($expected)
        ->and($policy->update($user, $menu))->toBe($expected)
        ->and($policy->delete($user, $menu))->toBe($expected);
})->with('appearance roles');

it('menerapkan matrix permission Widget untuk setiap peran', function (UserRole $role, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);
    $policy = new WidgetPolicy;
    $widget = new Widget;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user, $widget))->toBe($expected)
        ->and($policy->create($user))->toBe($expected)
        ->and($policy->update($user, $widget))->toBe($expected)
        ->and($policy->delete($user, $widget))->toBe($expected);
})->with('appearance roles');

it('menerapkan matrix permission Gallery untuk setiap peran', function (UserRole $role, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);
    $policy = new GalleryPolicy;
    $gallery = new Gallery;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user, $gallery))->toBe($expected)
        ->and($policy->create($user))->toBe($expected)
        ->and($policy->update($user, $gallery))->toBe($expected)
        ->and($policy->delete($user, $gallery))->toBe($expected);
})->with('content roles');

it('menerapkan matrix permission ContactMessage untuk setiap peran', function (UserRole $role, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);
    $policy = new ContactMessagePolicy;
    $contactMessage = new ContactMessage;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user, $contactMessage))->toBe($expected)
        ->and($policy->create($user))->toBe($expected)
        ->and($policy->update($user, $contactMessage))->toBe($expected)
        ->and($policy->delete($user, $contactMessage))->toBe($expected);
})->with('content roles');

it('menerapkan matrix Policy RatingCriterion untuk setiap peran', function (UserRole $role, bool $expected) {
    $user = User::factory()->create()->assignRole($role->value);
    $policy = new RatingCriterionPolicy;
    $ratingCriterion = new RatingCriterion;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user, $ratingCriterion))->toBe($expected)
        ->and($policy->create($user))->toBe($expected)
        ->and($policy->update($user, $ratingCriterion))->toBe($expected)
        ->and($policy->delete($user, $ratingCriterion))->toBe($expected);
})->with('admin only roles');

dataset('appearance roles', [
    'Admin' => [UserRole::Admin, true],
    'Editor' => [UserRole::Editor, false],
    'Author' => [UserRole::Author, false],
]);

dataset('content roles', [
    'Admin' => [UserRole::Admin, true],
    'Editor' => [UserRole::Editor, true],
    'Author' => [UserRole::Author, false],
]);

dataset('admin only roles', [
    'Admin' => [UserRole::Admin, true],
    'Editor' => [UserRole::Editor, false],
    'Author' => [UserRole::Author, false],
]);
