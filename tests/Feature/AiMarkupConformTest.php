<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Ai\Tasks\MarkupConformTask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('POST /admin/ai/markup-conform mengembalikan suggestion (mocked)', function () {
    $admin = User::where('email', config('admin.email'))->first();

    $mock = Mockery::mock(MarkupConformTask::class);
    $mock->shouldReceive('suggest')->andReturn('<p>rapi</p>');
    app()->instance(MarkupConformTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/markup-conform', [
        'source_html' => '<p>berantakan</p>',
    ]);

    $response->assertOk()->assertJson(['suggestion' => '<p>rapi</p>']);
});

it('hasil markup-conform disanitasi sebelum dikembalikan', function () {
    $admin = User::where('email', config('admin.email'))->first();

    $mock = Mockery::mock(MarkupConformTask::class);
    $mock->shouldReceive('suggest')->andReturn('<script>alert(1)</script><p>ok</p>');
    app()->instance(MarkupConformTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/markup-conform', [
        'source_html' => '<p>berantakan</p>',
    ]);

    $response->assertOk();
    expect($response->json('suggestion'))
        ->not->toContain('<script>')
        ->toContain('<p>ok</p>');
});

it('Editor (bukan Admin) mendapat 403 pada markup-conform', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $mock = Mockery::mock(MarkupConformTask::class);
    $mock->shouldReceive('suggest')->andReturn('<p>rapi</p>');
    app()->instance(MarkupConformTask::class, $mock);

    $this->actingAs($editor)->postJson('/admin/ai/markup-conform', [
        'source_html' => '<p>berantakan</p>',
    ])->assertForbidden();
});

it('User dengan hanya access-admin mendapat 403 pada markup-conform', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');

    $mock = Mockery::mock(MarkupConformTask::class);
    $mock->shouldReceive('suggest')->andReturn('<p>rapi</p>');
    app()->instance(MarkupConformTask::class, $mock);

    $this->actingAs($user)->postJson('/admin/ai/markup-conform', [
        'source_html' => '<p>berantakan</p>',
    ])->assertForbidden();
});

it('Guest tidak bisa akses endpoint markup-conform', function () {
    $this->postJson('/admin/ai/markup-conform', [
        'source_html' => '<p>berantakan</p>',
    ])->assertUnauthorized();
});
