<?php

use App\Enums\UserRole;
use App\Models\ContentType;
use App\Models\Post;
use App\Models\User;

beforeEach(function () {
    $this->seed();
    $this->type = ContentType::query()->firstOrFail();
});

it('Author boleh update & hapus post miliknya sendiri', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);

    expect($author->can('update', $post))->toBeTrue()
        ->and($author->can('delete', $post))->toBeTrue()
        ->and($author->can('deleteOwn', $post))->toBeTrue();
});

it('Author tidak boleh update post milik orang lain', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $other = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->create([
        'type_id' => $this->type->id,
        'author_id' => $other->id,
    ]);

    expect($author->can('update', $post))->toBeFalse()
        ->and($author->can('deleteOwn', $post))->toBeFalse();
});

it('Admin dan Editor boleh update post siapa pun (termasuk tanpa pemilik)', function () {
    $admin = User::factory()->create()->assignRole(UserRole::Admin->value);
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $post = Post::factory()->create([
        'type_id' => $this->type->id,
        'author_id' => null,
    ]);

    expect($admin->can('update', $post))->toBeTrue()
        ->and($editor->can('update', $post))->toBeTrue();
});

it('Post::author mengembalikan relasi user pemilik', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $post = Post::factory()->create([
        'type_id' => $this->type->id,
        'author_id' => $author->id,
    ]);

    expect($post->author)->not->toBeNull()
        ->and($post->author->id)->toBe($author->id);
});
