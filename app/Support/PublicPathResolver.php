<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ContentType;
use App\Models\Language;
use App\Models\PageTranslation;
use App\Models\PostTranslation;

/**
 * Resolve path publik (tanpa prefix locale) ke jenis konten yang sesuai.
 */
class PublicPathResolver
{
    /**
     * @return array{kind: 'home'}
     *                             | array{kind: 'archive', contentType: ContentType}
     *                             | array{kind: 'single', post: \App\Models\Post, translation: PostTranslation, contentType: ContentType}
     *                             | array{kind: 'page', page: \App\Models\Page, translation: PageTranslation}
     *                             | array{kind: 'notFound'}
     */
    public static function resolve(string $path): array
    {
        $path = trim($path, '/');
        if ($path === '') {
            return ['kind' => 'home'];
        }

        $segments = explode('/', $path);
        $langId = Language::current()->id;

        // 2 segment: type/slug → single post
        if (count($segments) === 2) {
            [$typeSlug, $postSlug] = $segments;
            $type = ContentType::query()
                ->where('slug', $typeSlug)
                ->where('is_active', true)
                ->first();

            if ($type) {
                $translation = PostTranslation::query()
                    ->where('slug', $postSlug)
                    ->where('language_id', $langId)
                    ->whereHas('post', fn ($q) => $q->where('type_id', $type->id))
                    ->published()
                    ->first();

                if ($translation) {
                    return [
                        'kind' => 'single',
                        'post' => $translation->post,
                        'translation' => $translation,
                        'contentType' => $type,
                    ];
                }
            }

            return ['kind' => 'notFound'];
        }

        // 1 segment: archive content type atau page
        if (count($segments) === 1) {
            $slug = $segments[0];

            $type = ContentType::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($type) {
                return ['kind' => 'archive', 'contentType' => $type];
            }

            $pageTranslation = PageTranslation::query()
                ->where('slug', $slug)
                ->where('language_id', $langId)
                ->where('status', 'Published')
                ->whereHas('page')
                ->first();

            if ($pageTranslation) {
                return [
                    'kind' => 'page',
                    'page' => $pageTranslation->page,
                    'translation' => $pageTranslation,
                ];
            }
        }

        return ['kind' => 'notFound'];
    }
}
