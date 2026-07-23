<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Support\Facades\URL;

class PublicLocaleLinks
{
    /**
     * @return list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>
     */
    public static function home(): array
    {
        return self::build(fn (): string => '/');
    }

    /**
     * @return list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>
     */
    public static function archive(ContentType $contentType): array
    {
        return self::build(fn (): string => '/'.$contentType->slug);
    }

    /**
     * @return list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>
     */
    public static function page(Page $page): array
    {
        $translations = PageTranslation::query()
            ->where('page_id', $page->id)
            ->where('status', 'Published')
            ->whereHas('page')
            ->get(['language_id', 'slug'])
            ->keyBy('language_id');

        return self::build(function (Language $language) use ($translations): ?string {
            $translation = $translations->get($language->id);

            return $translation instanceof PageTranslation ? '/'.$translation->slug : null;
        });
    }

    /**
     * @return list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>
     */
    public static function post(Post $post, ContentType $contentType): array
    {
        $translations = PostTranslation::query()
            ->where('post_id', $post->id)
            ->published()
            ->whereHas('post')
            ->get(['language_id', 'slug'])
            ->keyBy('language_id');

        return self::build(function (Language $language) use ($translations, $contentType): ?string {
            $translation = $translations->get($language->id);

            return $translation instanceof PostTranslation
                ? "/{$contentType->slug}/{$translation->slug}"
                : null;
        });
    }

    /**
     * @param  list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>  $localeLinks
     * @return array<string, string>
     */
    public static function hreflang(array $localeLinks): array
    {
        $hreflang = [];

        foreach ($localeLinks as $link) {
            if ($link['url'] !== null) {
                $hreflang[$link['code']] = URL::to($link['url']);
            }
        }

        return $hreflang;
    }

    /**
     * @param  callable(Language): ?string  $pathForLanguage
     * @return list<array{code: string, name: string, url: ?string, isCurrent: bool, isAvailable: bool}>
     */
    private static function build(callable $pathForLanguage): array
    {
        return array_values(Language::active()
            ->get(['id', 'code', 'name'])
            ->map(function (Language $language) use ($pathForLanguage): array {
                $path = $pathForLanguage($language);

                return [
                    'code' => $language->code,
                    'name' => $language->name,
                    'url' => $path === null ? null : LocaleUrl::for($language->code, $path),
                    'isCurrent' => $language->code === app()->getLocale(),
                    'isAvailable' => $path !== null,
                ];
            })
            ->all());
    }
}
