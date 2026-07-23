<?php

declare(strict_types=1);

use App\Enums\PageMode;
use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('Editor dapat preview Template yang disanitasi tanpa menyimpan Page', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $countBefore = Page::query()->count();

    $this->actingAs($editor)->postJson('/admin/pages/preview', [
        'mode' => PageMode::Template->value,
        'template_key' => 'landing',
        'title' => 'Draft Landing',
        'content' => '<h2>Judul</h2><script>alert(1)</script><p onclick="evil()">Isi <strong>tebal</strong></p>',
    ])->assertOk()
        ->assertJsonPath('preview.title', 'Draft Landing')
        ->assertJsonPath('preview.template_key', 'landing')
        ->assertJsonPath('preview.mode', PageMode::Template->value)
        ->assertJsonPath('preview.content', '<h2>Judul</h2><p>Isi <strong>tebal</strong></p>');

    expect(Page::query()->count())->toBe($countBefore);
});

it('Editor tidak dapat preview Code sedangkan Admin dapat', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $payload = [
        'mode' => PageMode::Code->value,
        'template_key' => 'default',
        'title' => 'Code',
        'content' => '<section class="layout"><p>Isi</p></section><script>evil()</script>',
    ];

    $this->actingAs($editor)
        ->postJson('/admin/pages/preview', $payload)
        ->assertForbidden();

    $admin = User::where('email', config('admin.email'))->firstOrFail();
    $this->actingAs($admin)
        ->postJson('/admin/pages/preview', $payload)
        ->assertOk()
        ->assertJsonPath('preview.content', '<section class="layout"><p>Isi</p></section>');
});

it('preview menolak template key di luar registry', function () {
    $admin = User::where('email', config('admin.email'))->firstOrFail();

    $this->actingAs($admin)->postJson('/admin/pages/preview', [
        'mode' => PageMode::Template->value,
        'template_key' => 'uploaded-php',
        'title' => 'Tidak Aman',
        'content' => '<p>Isi</p>',
    ])->assertUnprocessable()->assertJsonValidationErrors('template_key');
});
