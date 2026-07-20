<?php

declare(strict_types=1);

use App\Enums\AiTask;
use App\Models\AiConfig;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
});

function settingsAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/settings/ai menampilkan kartu per task tanpa membocorkan api_key', function () {
    AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => true,
        'base_url' => 'https://ark.example/api/v3',
        'api_key' => 'rahasia-ark',
        'model' => 'seed-translation-250915',
    ]);

    $this->actingAs(settingsAdmin())
        ->get('/admin/settings/ai')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/ai/index')
            ->has('configs', 3)
            ->where('configs.0.task', 'Translation')
            ->where('configs.0.has_key', true)
            ->where('configs.0.model', 'seed-translation-250915')
            ->missing('configs.0.api_key')
        );
});

it('PUT membuat konfigurasi baru untuk sebuah task', function () {
    $this->actingAs(settingsAdmin())
        ->put('/admin/settings/ai/ContentRefinement', [
            'base_url' => 'https://api.meganova.ai/v1',
            'model' => 'meganova-ai/manta-flash-1.0',
            'system_prompt' => 'Kamu editor.',
            'enabled' => true,
            'api_key' => 'meganova-secret',
        ])
        ->assertRedirect();

    $config = AiConfig::resolve(AiTask::ContentRefinement);

    expect($config)->not->toBeNull()
        ->and($config->base_url)->toBe('https://api.meganova.ai/v1')
        ->and($config->model)->toBe('meganova-ai/manta-flash-1.0')
        ->and($config->api_key)->toBe('meganova-secret')
        ->and($config->enabled)->toBeTrue();
});

it('PUT dengan api_key kosong mempertahankan key lama', function () {
    $config = AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => true,
        'base_url' => 'https://old.example/v1',
        'api_key' => 'key-lama',
        'model' => 'old-model',
    ]);

    $this->actingAs(settingsAdmin())
        ->put('/admin/settings/ai/Translation', [
            'base_url' => 'https://new.example/v1',
            'model' => 'new-model',
            'system_prompt' => null,
            'enabled' => true,
            'api_key' => '',
        ])
        ->assertRedirect();

    $fresh = $config->fresh();

    expect($fresh->base_url)->toBe('https://new.example/v1')
        ->and($fresh->model)->toBe('new-model')
        ->and($fresh->api_key)->toBe('key-lama');
});

it('PUT dengan task tidak valid → 404', function () {
    $this->actingAs(settingsAdmin())
        ->put('/admin/settings/ai/BukanTask', [
            'enabled' => false,
        ])
        ->assertNotFound();
});

it('Non-admin (tanpa admin.access-system) ditolak', function () {
    $editor = User::factory()->create()->givePermissionTo('access-admin');

    $this->actingAs($editor)->get('/admin/settings/ai')->assertForbidden();
    $this->actingAs($editor)
        ->put('/admin/settings/ai/Translation', ['enabled' => false])
        ->assertForbidden();
});
