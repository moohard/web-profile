<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use App\Support\LocaleUrl;
use App\Support\Posts\PostFeaturedImage;
use App\Support\PublicLayoutProps;
use App\Support\PublicLocaleLinks;
use App\Support\Seo\SeoProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Arsip post per content type (locale aktif).
     */
    public function archive(Request $request, ContentType $contentType): Response
    {
        $langId = Language::current()->id;

        $posts = PostTranslation::query()
            ->with([
                'post.type',
                'post.category.translations',
                'post.media',
            ])
            ->where('language_id', $langId)
            ->whereHas('post', fn ($q) => $q->where('type_id', $contentType->id))
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString()
            ->through(function (PostTranslation $translation) use ($contentType, $langId): array {
                $post = $translation->post;

                return [
                    'id' => $translation->id,
                    'title' => $translation->title,
                    'url' => LocaleUrl::for(
                        app()->getLocale(),
                        '/'.$contentType->slug.'/'.$translation->slug,
                    ),
                    'excerpt' => excerpt($translation->body),
                    'featured' => PostFeaturedImage::from($post->getFirstMedia('featured')),
                    'published_at' => $translation->published_at?->toIso8601String(),
                    'category' => $this->categoryData($post, $langId),
                ];
            });

        $localeLinks = PublicLocaleLinks::archive($contentType);

        $name = $this->contentTypeName($contentType);
        $contentTypeDescription = $this->contentTypeDescription($contentType);
        $firstPost = $posts->items()[0] ?? null;
        $fallbackDescription = $firstPost !== null ? $firstPost['excerpt'] : null;
        $description = $contentTypeDescription !== null && $contentTypeDescription !== ''
            ? $contentTypeDescription
            : $fallbackDescription;

        $seo = SeoProps::for(
            title: $name,
            description: $description,
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault(PublicLocaleLinks::hreflang($localeLinks)),
            ogType: 'website',
        );

        return Inertia::render('public/post-archive', array_merge(
            PublicLayoutProps::base($localeLinks),
            [
                'region' => PublicLayoutProps::region(WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE, (string) $contentType->id),
                'contentType' => [
                    'slug' => $contentType->slug,
                    'name' => $name,
                ],
                'posts' => $posts,
                'seo' => $seo,
            ],
        ));
    }

    /**
     * Single post (translation published untuk locale aktif) + props SEO.
     */
    public function show(Request $request, ContentType $contentType, PostTranslation $translation): Response
    {
        $langId = Language::current()->id;
        $post = $translation->post()
            ->with([
                'type',
                'category.translations',
                'tags.translations',
                'media',
            ])
            ->firstOrFail();
        $localeLinks = PublicLocaleLinks::post($post, $contentType);
        $body = app(Sanitizer::class)->cleanRichText($translation->body ?? '');
        $description = filled($translation->meta_description)
            ? $translation->meta_description
            : excerpt($body);
        $featured = PostFeaturedImage::from($post->getFirstMedia('featured'));
        $featuredUrl = $featured !== null ? URL::to($featured['src']) : null;
        $category = $this->categoryData($post, $langId);
        $tags = $this->tagData($post, $langId);

        $seo = SeoProps::for(
            title: $translation->meta_title ?? $translation->title,
            description: $description,
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault(PublicLocaleLinks::hreflang($localeLinks)),
            ogType: 'article',
            ogImage: $featuredUrl,
        );

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation->title,
            'datePublished' => $translation->published_at?->toIso8601String(),
            'dateModified' => $translation->updated_at?->toIso8601String(),
            'description' => $description,
            'image' => $featuredUrl !== null ? [$featuredUrl] : [],
            'articleSection' => $category['name'] ?? null,
            'keywords' => array_column($tags, 'name'),
            'inLanguage' => app()->getLocale(),
        ];

        return Inertia::render('public/post-show', array_merge(
            PublicLayoutProps::base($localeLinks),
            [
                'region' => PublicLayoutProps::region(WidgetPlacementTarget::TYPE_CONTENT_SINGLE, (string) $post->id),
                'post' => [
                    'id' => $translation->id,
                    'slug' => $translation->slug,
                    'title' => $translation->title,
                    'body' => $body,
                    'published_at' => $translation->published_at?->toIso8601String(),
                    'featured' => $featured,
                    'category' => $category,
                    'tags' => $tags,
                ],
                'contentType' => [
                    'slug' => $contentType->slug,
                    'name' => $this->contentTypeName($contentType),
                ],
                'seo' => $seo,
                'jsonLd' => $jsonLd,
            ],
        ));
    }

    private function contentTypeName(ContentType $contentType): string
    {
        return $contentType->translations()
            ->where('language_id', Language::current()->id)
            ->value('name') ?? ucfirst($contentType->slug);
    }

    private function contentTypeDescription(ContentType $contentType): ?string
    {
        return $contentType->translations()
            ->where('language_id', Language::current()->id)
            ->value('description');
    }

    /**
     * @return null|array{id: int, name: string}
     */
    private function categoryData(Post $post, int $languageId): ?array
    {
        $category = $post->category;

        if ($category === null) {
            return null;
        }

        $translation = $category->translations->firstWhere('language_id', $languageId)
            ?? $category->translations->first();

        return [
            'id' => $category->id,
            'name' => $translation !== null ? $translation->name : $category->slug,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function tagData(Post $post, int $languageId): array
    {
        return $post->tags
            ->map(function (Tag $tag) use ($languageId): array {
                $translation = $tag->translations->firstWhere('language_id', $languageId)
                    ?? $tag->translations->first();

                return [
                    'id' => (int) $tag->id,
                    'name' => $translation !== null ? (string) $translation->name : $tag->slug,
                ];
            })
            ->values()
            ->all();
    }
}
