<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Tag;
use App\Models\WidgetPlacementTarget;
use App\Services\Html\Sanitizer;
use App\Support\LocaleUrl;
use App\Support\PublicLayoutProps;
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
            ->with(['post.type', 'post.media'])
            ->where('language_id', $langId)
            ->whereHas('post', fn ($q) => $q->where('type_id', $contentType->id))
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12)
            ->through(fn (PostTranslation $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'excerpt' => excerpt($t->body),
                'url' => LocaleUrl::for(app()->getLocale(), '/'.$contentType->slug.'/'.$t->slug),
                'image_url' => $this->featuredImageUrl($t->post, 'webp_medium'),
                'image_srcset' => $this->featuredImageSrcset($t->post),
                'published_at' => $t->published_at?->toIso8601String(),
            ]);

        // Slug content type sama di semua bahasa → hreflang = 1 URL arsip per locale aktif.
        $hreflang = [];
        foreach (Language::active()->get() as $language) {
            $hreflang[$language->code] = URL::to(LocaleUrl::for($language->code, '/'.$contentType->slug));
        }

        $name = $this->contentTypeName($contentType);

        $seo = SeoProps::for(
            title: $name,
            description: $this->contentTypeDescription($contentType),
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault($hreflang),
            ogType: 'website',
        );

        return Inertia::render('public/post-archive', array_merge(
            PublicLayoutProps::base(),
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
        $post = $translation->post()->firstOrFail();
        $post->load(['category.translations', 'tags.translations']);
        $allTranslations = $post->translations()->published()->with('language')->get();
        $locale = app()->getLocale();

        $hreflang = [];
        foreach ($allTranslations as $tr) {
            $path = "/{$contentType->slug}/{$tr->slug}";
            $languageCode = $tr->language->code;
            $hreflang[$languageCode] = URL::to(LocaleUrl::for($languageCode, $path));
        }

        $ogImageUrl = $this->featuredImageUrl($post, 'webp_large');
        $metaDescription = $translation->meta_description ?: excerpt($translation->body);

        $seo = SeoProps::for(
            title: $translation->meta_title ?? $translation->title,
            description: $metaDescription,
            canonical: url()->current(),
            hreflang: SeoProps::withXDefault($hreflang),
            ogType: 'article',
            ogImage: $ogImageUrl !== null ? URL::to($ogImageUrl) : null,
        );

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation->title,
            'datePublished' => $translation->published_at?->toIso8601String(),
            'image' => $ogImageUrl !== null ? [URL::to($ogImageUrl)] : [],
            'inLanguage' => app()->getLocale(),
        ];

        // Defense-in-depth: sanitasi HTML body (profil rich-text/default, sama seperti
        // saat disimpan) sebelum dikirim ke frontend (dirender via dangerouslySetInnerHTML)
        $translation->body = app(Sanitizer::class)->cleanRichText($translation->body ?? '');

        return Inertia::render('public/post-show', array_merge(
            PublicLayoutProps::base(),
            [
                'region' => PublicLayoutProps::region(WidgetPlacementTarget::TYPE_CONTENT_SINGLE, (string) $post->id),
                'post' => $translation->load('post.type'),
                'contentType' => [
                    'slug' => $contentType->slug,
                    'name' => $this->contentTypeName($contentType),
                ],
                'category' => $this->categoryProp($post->category, $locale),
                'tags' => $this->tagsProp($post, $locale),
                'seo' => $seo,
                'jsonLd' => $jsonLd,
            ],
        ));
    }

    /**
     * URL gambar dari koleksi media `featured`, memakai konversi WebP yang diminta
     * bila sudah selesai diproses (queued); fallback ke file asli bila belum tersedia.
     */
    private function featuredImageUrl(?Post $post, string $conversion): ?string
    {
        $media = $post?->getFirstMedia('featured');

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }

    /**
     * Srcset responsif dari konversi `webp_large` — satu-satunya konversi yang
     * mengaktifkan withResponsiveImages(). Null bila media/variannya belum tersedia.
     */
    private function featuredImageSrcset(?Post $post): ?string
    {
        $media = $post?->getFirstMedia('featured');

        if ($media === null || ! $media->hasGeneratedConversion('webp_large')) {
            return null;
        }

        return $media->getSrcset('webp_large') ?: null;
    }

    /**
     * @return array{slug: string, name: string}|null
     */
    private function categoryProp(?Category $category, string $locale): ?array
    {
        if ($category === null) {
            return null;
        }

        $languageId = Language::idFor($locale);
        $translation = $category->translations->firstWhere('language_id', $languageId)
            ?? $category->translations->first();

        return [
            'slug' => $category->slug,
            'name' => $translation !== null ? $translation->name : $category->slug,
        ];
    }

    /**
     * @return array<int, array{slug: string, name: string}>
     */
    private function tagsProp(Post $post, string $locale): array
    {
        $languageId = Language::idFor($locale);

        return $post->tags
            ->map(function (Tag $tag) use ($languageId): array {
                $translation = $tag->translations->firstWhere('language_id', $languageId)
                    ?? $tag->translations->first();

                return [
                    'slug' => $tag->slug,
                    'name' => $translation !== null ? $translation->name : $tag->slug,
                ];
            })
            ->values()
            ->all();
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
}
