<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Widgets\SyncWidgetPlacements;
use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WidgetRequest;
use App\Models\Language;
use App\Models\Widget;
use App\Models\WidgetPlacementTarget;
use App\Models\WidgetTranslation;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WidgetController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Widget::class);

        return Inertia::render('admin/widgets/index', [
            'widgets' => Widget::query()
                ->with(['translations.language', 'placements.targets'])
                ->orderByDesc('id')
                ->get()
                ->map(fn (Widget $widget): array => $this->widgetData($widget))
                ->all(),
            'languages' => Language::active()
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn (Language $language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                ])
                ->all(),
            'positions' => array_map(fn (WidgetPosition $position): array => [
                'value' => $position->value,
                'label' => $position->label(),
            ], WidgetPosition::cases()),
            'scopes' => array_map(fn (PlacementScope $scope): array => [
                'value' => $scope->value,
                'label' => $scope->label(),
            ], PlacementScope::cases()),
            'targetTypes' => [
                WidgetPlacementTarget::TYPE_PAGE,
                WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE,
                WidgetPlacementTarget::TYPE_CONTENT_SINGLE,
            ],
        ]);
    }

    public function store(WidgetRequest $request, SyncWidgetPlacements $syncPlacements): RedirectResponse
    {
        $this->authorize('create', Widget::class);

        $data = $request->validated();

        DB::transaction(function () use ($data, $syncPlacements): void {
            $widget = Widget::query()->create([
                'type' => $data['type'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncTranslations($widget, $data['translations']);
            $syncPlacements($widget, $data['placements']);
        });

        PublicLayoutProps::flushCache();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Widget berhasil dibuat.']);

        return to_route('admin.widgets.index');
    }

    public function update(WidgetRequest $request, Widget $widget, SyncWidgetPlacements $syncPlacements): RedirectResponse
    {
        $this->authorize('update', $widget);

        $data = $request->validated();

        DB::transaction(function () use ($data, $syncPlacements, $widget): void {
            $widget->update([
                'type' => $data['type'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncTranslations($widget, $data['translations']);
            $syncPlacements($widget, $data['placements']);
        });

        PublicLayoutProps::flushCache();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Widget berhasil diperbarui.']);

        return to_route('admin.widgets.index');
    }

    public function destroy(Widget $widget): RedirectResponse
    {
        $this->authorize('delete', $widget);

        $widget->delete();

        PublicLayoutProps::flushCache();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Widget berhasil dihapus.']);

        return to_route('admin.widgets.index');
    }

    /**
     * @param  list<array{language_id: int, title: ?string, content: ?string}>  $translations
     */
    private function syncTranslations(Widget $widget, array $translations): void
    {
        $languageIds = array_column($translations, 'language_id');
        $widget->translations()->whereNotIn('language_id', $languageIds)->delete();

        foreach ($translations as $translation) {
            WidgetTranslation::query()->updateOrCreate(
                ['widget_id' => $widget->id, 'language_id' => $translation['language_id']],
                ['title' => $translation['title'], 'content' => $translation['content']],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function widgetData(Widget $widget): array
    {
        return [
            'id' => $widget->id,
            'type' => $widget->type,
            'is_active' => $widget->is_active,
            'translations' => $widget->translations->map(fn (WidgetTranslation $translation): array => [
                'language_id' => $translation->language_id,
                'language_code' => $translation->language?->code,
                'title' => $translation->title,
                'content' => $translation->content,
            ])->values()->all(),
            'placements' => $widget->placements->map(fn ($placement): array => [
                'id' => $placement->id,
                'position' => $placement->position->value,
                'scope' => $placement->scope->value,
                'sort_order' => $placement->sort_order,
                'targets' => $placement->targets->map(fn (WidgetPlacementTarget $target): array => [
                    'target_type' => $target->target_type,
                    'target_ref' => $target->target_ref,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
