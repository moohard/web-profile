<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MenuLocation;
use App\Models\Language;
use App\Models\Menu;
use App\Models\WidgetPlacement;
use Illuminate\Support\Facades\Cache;

/**
 * Compose props layout publik (menu, locale, region/widget) dari DB.
 */
class PublicLayoutProps
{
    /**
     * Props dasar untuk seluruh halaman publik (cache 1 jam per bahasa).
     *
     * @return array{
     *     locale: string,
     *     locales: list<array{code: string, name: string}>,
     *     headerMenu: list<array{label: string, url: string}>,
     *     footerMenu: list<array{label: string, url: string}>,
     *     region: array{
     *         widgets: array{
     *             beforeContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *             afterContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *             sidebar: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *             footer: list<array{type: string, config: mixed, title: ?string, content: ?string}>
     *         }
     *     }
     * }
     */
    public static function base(): array
    {
        $langId = Language::current()->id;

        return Cache::remember("public_layout.{$langId}", now()->addHour(), function () use ($langId) {
            $headerMenu = static::resolveMenu(MenuLocation::Header, $langId);
            $footerMenu = static::resolveMenu(MenuLocation::Footer, $langId);
            $widgets = static::resolveWidgets($langId);
            $locales = Language::active()
                ->get(['code', 'name'])
                ->map(fn (Language $lang) => [
                    'code' => $lang->code,
                    'name' => $lang->name,
                ])
                ->values()
                ->all();

            return [
                'locale' => app()->getLocale(),
                'locales' => $locales,
                'headerMenu' => $headerMenu,
                'footerMenu' => $footerMenu,
                'region' => [
                    'widgets' => $widgets,
                ],
            ];
        });
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private static function resolveMenu(MenuLocation $location, int $langId): array
    {
        $menu = Menu::query()->at($location)->first();

        if (! $menu) {
            return [];
        }

        return $menu->items()
            ->with(['translations' => fn ($q) => $q->where('language_id', $langId)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->translations->first()?->label ?? '',
                'url' => $item->url ?? '#',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     beforeContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     afterContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     sidebar: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     footer: list<array{type: string, config: mixed, title: ?string, content: ?string}>
     * }
     */
    private static function resolveWidgets(int $langId): array
    {
        $byPosition = [
            'beforeContent' => [],
            'afterContent' => [],
            'sidebar' => [],
            'footer' => [],
        ];

        $positionMap = [
            'BeforeContent' => 'beforeContent',
            'AfterContent' => 'afterContent',
            'Sidebar' => 'sidebar',
            'Footer' => 'footer',
        ];

        $placements = WidgetPlacement::query()
            ->with(['widget.translations' => fn ($q) => $q->where('language_id', $langId)])
            ->whereHas('widget', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        foreach ($placements as $placement) {
            $widget = $placement->widget;

            if ($widget === null) {
                continue;
            }

            $translation = $widget->translations->first();
            $key = $positionMap[$placement->position->value] ?? null;

            if ($key === null) {
                continue;
            }

            $byPosition[$key][] = [
                'type' => $widget->type,
                'config' => $widget->config,
                'title' => $translation?->title,
                'content' => $translation?->content,
            ];
        }

        return $byPosition;
    }
}
