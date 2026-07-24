<?php

declare(strict_types=1);

use App\Enums\PlacementScope;
use App\Enums\UserRole;
use App\Enums\WidgetPosition;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetPlacementTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->withoutVite();
    Language::flushCache();
    Cache::flush();
});

function widgetCrudAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

function widgetPayload(array $overrides = []): array
{
    $languageId = Language::idFor('id');

    return array_replace_recursive([
        'type' => 'HtmlWidget',
        'is_active' => true,
        'translations' => [[
            'language_id' => $languageId,
            'title' => 'Widget pengumuman',
            'content' => '<p>Informasi terbaru</p>',
        ]],
        'placements' => [[
            'position' => WidgetPosition::Sidebar->value,
            'scope' => PlacementScope::All->value,
            'sort_order' => 2,
            'targets' => [],
        ]],
    ], $overrides);
}

it('Admin dapat melihat daftar widget', function () {
    $widget = Widget::factory()->create(['type' => 'HtmlWidget']);

    $this->actingAs(widgetCrudAdmin())
        ->get('/admin/widgets')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/widgets/index')
            ->has('widgets', 1)
            ->where('widgets.0.id', $widget->id)
        );
});

it('Admin dapat membuat HtmlWidget dengan terjemahan dan placement target', function () {
    $contentType = ContentType::query()->where('slug', 'berita')->firstOrFail();

    $this->actingAs(widgetCrudAdmin())
        ->post('/admin/widgets', widgetPayload([
            'placements' => [[
                'position' => WidgetPosition::Sidebar->value,
                'scope' => PlacementScope::Only->value,
                'sort_order' => 5,
                'targets' => [[
                    'target_type' => WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE,
                    'target_ref' => (string) $contentType->id,
                ]],
            ]],
        ]))
        ->assertRedirect('/admin/widgets');

    $widget = Widget::query()->with(['translations', 'placements.targets'])->firstOrFail();

    expect($widget->type)->toBe('HtmlWidget')
        ->and($widget->is_active)->toBeTrue()
        ->and($widget->translations)->toHaveCount(1)
        ->and($widget->translations->first()?->title)->toBe('Widget pengumuman')
        ->and($widget->placements)->toHaveCount(1)
        ->and($widget->placements->first()?->position)->toBe(WidgetPosition::Sidebar)
        ->and($widget->placements->first()?->scope)->toBe(PlacementScope::Only)
        ->and($widget->placements->first()?->targets->first()?->target_type)->toBe(WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE)
        ->and($widget->placements->first()?->targets->first()?->target_ref)->toBe((string) $contentType->id);
});

it('Admin dapat memperbarui widget beserta placement-nya', function () {
    $widget = Widget::factory()->create(['type' => 'HtmlWidget', 'is_active' => true]);

    $this->actingAs(widgetCrudAdmin())
        ->put("/admin/widgets/{$widget->id}", widgetPayload([
            'is_active' => false,
            'translations' => [[
                'language_id' => Language::idFor('id'),
                'title' => 'Judul diperbarui',
                'content' => '<p>Konten diperbarui</p>',
            ]],
            'placements' => [[
                'position' => WidgetPosition::Footer->value,
                'scope' => PlacementScope::Except->value,
                'sort_order' => 1,
                'targets' => [[
                    'target_type' => WidgetPlacementTarget::TYPE_PAGE,
                    'target_ref' => '12',
                ]],
            ]],
        ]))
        ->assertRedirect('/admin/widgets');

    $widget->refresh()->load(['translations', 'placements.targets']);

    expect($widget->is_active)->toBeFalse()
        ->and($widget->translations->first()?->title)->toBe('Judul diperbarui')
        ->and($widget->placements->sole()->position)->toBe(WidgetPosition::Footer)
        ->and($widget->placements->sole()->scope)->toBe(PlacementScope::Except)
        ->and($widget->placements->sole()->targets->sole()->target_ref)->toBe('12');
});

it('Admin dapat menghapus widget', function () {
    $widget = Widget::factory()->create(['type' => 'HtmlWidget']);

    $this->actingAs(widgetCrudAdmin())
        ->delete("/admin/widgets/{$widget->id}")
        ->assertRedirect('/admin/widgets');

    $this->assertModelMissing($widget);
});

it('widget scope Only untuk arsip tidak tampil di beranda', function () {
    $contentType = ContentType::query()->where('slug', 'berita')->firstOrFail();

    $this->actingAs(widgetCrudAdmin())
        ->post('/admin/widgets', widgetPayload([
            'placements' => [[
                'position' => WidgetPosition::Sidebar->value,
                'scope' => PlacementScope::Only->value,
                'sort_order' => 0,
                'targets' => [[
                    'target_type' => WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE,
                    'target_ref' => (string) $contentType->id,
                ]],
            ]],
        ]))
        ->assertRedirect('/admin/widgets');

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page->has('region.widgets.sidebar', 0));

    $this->get('/berita')
        ->assertInertia(fn (Assert $page) => $page
            ->has('region.widgets.sidebar', 1)
            ->where('region.widgets.sidebar.0.title', 'Widget pengumuman')
        );
});

it('hanya Admin yang dapat mengelola widget', function (UserRole $role) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)
        ->get('/admin/widgets')
        ->assertForbidden();
})->with([
    UserRole::Editor,
    UserRole::Author,
]);
