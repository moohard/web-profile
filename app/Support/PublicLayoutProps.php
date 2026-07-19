<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MenuLocation;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use App\Models\WidgetPlacement;
use App\Services\Html\Sanitizer;
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
     *     locales: array<int, array{code: string, name: string}>,
     *     headerMenu: array<int, array{label: string, url: string}>,
     *     footerMenu: array<int, array{label: string, url: string}>,
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
            $headerMenu = self::resolveMenu(MenuLocation::Header, $langId);
            $footerMenu = self::resolveMenu(MenuLocation::Footer, $langId);
            $widgets = self::resolveWidgets($langId);
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
     * @return array<int, array{label: string, url: string}>
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
            ->map(function (MenuItem $item): array {
                $translation = $item->translations->first();

                return [
                    'label' => $translation instanceof MenuItemTranslation ? $translation->label : '',
                    'url' => $item->url ?? '#',
                ];
            })
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
            $key = $positionMap[$placement->position->value];

            $content = $translation?->content;

            // Defense-in-depth: sanitasi HTML widget saat disajikan ke publik
            if ($widget->type === 'HtmlWidget' && is_string($content) && $content !== '') {
                $content = app(Sanitizer::class)->clean($content);
            }

            $byPosition[$key][] = [
                'type' => $widget->type,
                'config' => $widget->config,
                'title' => $translation?->title,
                'content' => $content,
            ];
        }

        return $byPosition;
    }
}
