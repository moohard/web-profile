<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\LinkType;
use App\Enums\MenuLocation;
use App\Enums\PlacementScope;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\WidgetPlacement;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use Illuminate\Support\Facades\Cache;

/**
 * Compose props layout publik (menu, locale, region/widget) dari DB.
 */
class PublicLayoutProps
{
    public static function flushCache(): void
    {
        foreach (Language::query()->pluck('id') as $languageId) {
            Cache::forget("public_layout.{$languageId}");
        }
    }

    /**
     * Props global untuk seluruh halaman publik (cache 1 jam per bahasa).
     * Menu & locale tidak bergantung konteks halaman, jadi aman di-cache.
     *
     * @param  list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>  $localeLinks
     * @return array{
     *     locale: string,
     *     homeUrl: string,
     *     localeLinks: list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>,
     *     headerMenu: array<int, array<string, mixed>>,
     *     footerMenu: array<int, array<string, mixed>>
     * }
     */
    public static function base(array $localeLinks): array
    {
        $langId = Language::current()->id;

        $cached = Cache::remember("public_layout.{$langId}", now()->addHour(), function () use ($langId) {
            $headerMenu = self::resolveMenu(MenuLocation::Header, $langId);
            $footerMenu = self::resolveMenu(MenuLocation::Footer, $langId);

            return [
                'locale' => app()->getLocale(),
                'homeUrl' => LocaleUrl::for(app()->getLocale(), '/'),
                'headerMenu' => $headerMenu,
                'footerMenu' => $footerMenu,
            ];
        });

        return [
            ...$cached,
            'localeLinks' => $localeLinks,
        ];
    }

    /**
     * Region (widget) yang difilter sesuai konteks halaman.
     * TIDAK di-cache global karena bergantung target_type/target_ref
     * (scope All/Only/Except di widget_placement_targets).
     *
     * @return array{widgets: array{
     *     beforeContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     afterContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     sidebar: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     footer: list<array{type: string, config: mixed, title: ?string, content: ?string}>
     * }}
     */
    public static function region(?string $targetType = null, ?string $targetRef = null): array
    {
        return [
            'widgets' => self::resolveWidgets(Language::current()->id, $targetType, $targetRef),
        ];
    }

    /**
     * Resolve menu jadi struktur pohon dengan URL nyata per link_type.
     *
     * @return array<int, array{label: string, url: string, children: array<int, array<string, mixed>>}>
     */
    private static function resolveMenu(MenuLocation $location, int $langId): array
    {
        $menu = Menu::query()->at($location)->first();

        if (! $menu) {
            return [];
        }

        return $menu->items()
            ->with([
                'translations' => fn ($q) => $q->where('language_id', $langId),
                'children' => fn ($q) => $q->orderBy('sort_order')
                    ->with(['translations' => fn ($q2) => $q2->where('language_id', $langId)]),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (MenuItem $item) => self::mapMenuItem($item, $langId))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, url: string, children: array<int, array<string, mixed>>}|null
     */
    private static function mapMenuItem(MenuItem $item, int $langId): ?array
    {
        $translation = $item->translations->first();
        $url = self::resolveMenuItemUrl($item, $langId);

        if ($url === null) {
            return null;
        }

        $children = $item->relationLoaded('children')
            ? $item->children
                ->map(fn (MenuItem $child) => self::mapMenuItem($child, $langId))
                ->filter()
                ->values()
                ->all()
            : [];

        return [
            'label' => $translation instanceof MenuItemTranslation ? $translation->label : '',
            'url' => $url,
            'children' => $children,
        ];
    }

    /**
     * Bangun URL menu berdasarkan link_type + link_ref.
     * URL manual (Url) dibiarkan apa adanya; target internal di-locale-prefix.
     */
    private static function resolveMenuItemUrl(MenuItem $item, int $langId): ?string
    {
        $locale = app()->getLocale();

        return match ($item->link_type) {
            LinkType::Url => $item->url ?? '#',
            LinkType::ContentArchive => self::localePath($locale, self::archivePath($item->link_ref)),
            LinkType::ContentSingle => self::localePath($locale, self::singlePath($item->link_ref, $langId)),
            LinkType::Page => self::localePath($locale, self::pagePath($item->link_ref, $langId)),
        };
    }

    /** Locale-prefix path internal; kembalikan null bila target tak ditemukan. */
    private static function localePath(string $locale, ?string $path): ?string
    {
        return $path === null ? null : LocaleUrl::for($locale, $path);
    }

    private static function archivePath(?string $ref): ?string
    {
        if ($ref === null) {
            return null;
        }

        $type = ContentType::query()->whereKey($ref)->where('is_active', true)->first();

        return $type ? '/'.$type->slug : null;
    }

    private static function singlePath(?string $ref, int $langId): ?string
    {
        if ($ref === null) {
            return null;
        }

        $post = Post::query()->with('type')->find($ref);

        if ($post === null || $post->type === null) {
            return null;
        }

        $translation = $post->translations()
            ->where('language_id', $langId)
            ->published()
            ->first();

        return $translation ? '/'.$post->type->slug.'/'.$translation->slug : null;
    }

    private static function pagePath(?string $ref, int $langId): ?string
    {
        if ($ref === null) {
            return null;
        }

        $translation = PageTranslation::query()
            ->where('page_id', $ref)
            ->where('language_id', $langId)
            ->where('status', 'Published')
            ->whereHas('page')
            ->first();

        return $translation ? '/'.$translation->slug : null;
    }

    /**
     * @return array{
     *     beforeContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     afterContent: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     sidebar: list<array{type: string, config: mixed, title: ?string, content: ?string}>,
     *     footer: list<array{type: string, config: mixed, title: ?string, content: ?string}>
     * }
     */
    private static function resolveWidgets(int $langId, ?string $targetType = null, ?string $targetRef = null): array
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
            ->with(['widget.translations' => fn ($q) => $q->where('language_id', $langId), 'targets'])
            ->whereHas('widget', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        foreach ($placements as $placement) {
            $widget = $placement->widget;

            if ($widget === null) {
                continue;
            }

            if (! self::placementVisible($placement, $targetType, $targetRef)) {
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

    /**
     * Tentukan apakah placement tampil pada konteks halaman saat ini.
     * All → selalu; Only → hanya bila ada target cocok; Except → kecuali target cocok.
     */
    private static function placementVisible(WidgetPlacement $placement, ?string $targetType, ?string $targetRef): bool
    {
        if ($placement->scope === PlacementScope::All) {
            return true;
        }

        $matches = $targetType !== null && $placement->targets->contains(
            fn (WidgetPlacementTarget $target) => $target->target_type === $targetType
                && (string) $target->target_ref === (string) $targetRef
        );

        return $placement->scope === PlacementScope::Only ? $matches : ! $matches;
    }
}
