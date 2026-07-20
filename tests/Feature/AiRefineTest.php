<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Ai\Tasks\ContentRefinementTask;
use App\Services\Ai\Tasks\TranslationTask;

beforeEach(function () {
    $this->seed();
});

it('POST /admin/ai/refine mengembalikan suggestion (mocked)', function () {
    $admin = User::where('email', config('admin.email'))->first();

    $mock = Mockery::mock(ContentRefinementTask::class);
    $mock->shouldReceive('suggest')->andReturn('hasil');
    app()->instance(ContentRefinementTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/refine', [
        'source_text' => 'teks kasar yang perlu dikoreksi',
    ]);

    $response->assertOk()->assertJson(['suggestion' => 'hasil']);
});

it('POST /admin/ai/refine tanpa permission ai.update mendapat 403', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');

    $mock = Mockery::mock(ContentRefinementTask::class);
    $mock->shouldReceive('suggest')->andReturn('hasil');
    app()->instance(ContentRefinementTask::class, $mock);

    $this->actingAs($user)->postJson('/admin/ai/refine', [
        'source_text' => 'teks kasar',
    ])->assertForbidden();
});

it('POST /admin/ai/translate dengan source_text (tanpa entity) mengembalikan suggestion', function () {
    $admin = User::where('email', config('admin.email'))->first();

    $mock = Mockery::mock(TranslationTask::class);
    $mock->shouldReceive('translate')->andReturn('Hello world');
    app()->instance(TranslationTask::class, $mock);

    $response = $this->actingAs($admin)->postJson('/admin/ai/translate', [
        'source_locale' => 'id',
        'target_locale' => 'en',
        'source_text' => 'Halo dunia',
    ]);

    $response->assertOk()->assertJson(['suggestion' => 'Hello world']);
});
