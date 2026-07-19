<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\PostTranslation;
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
            ->with('post.type')
            ->where('language_id', $langId)
            ->whereHas('post', fn ($q) => $q->where('type_id', $contentType->id))
            ->published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('public/post-archive', array_merge(
            PublicLayoutProps::base(),
            [
                'contentType' => [
                    'slug' => $contentType->slug,
                    'name' => $this->contentTypeName($contentType),
                ],
                'posts' => $posts,
            ],
        ));
    }

    /**
     * Single post (translation published untuk locale aktif) + props SEO.
     */
    public function show(Request $request, ContentType $contentType, PostTranslation $translation): Response
    {
        $post = $translation->post()->firstOrFail();
        $allTranslations = $post->translations()->published()->with('language')->get();

        $hreflang = [];
        foreach ($allTranslations as $tr) {
            $path = "/{$contentType->slug}/{$tr->slug}";
            $languageCode = $tr->language->code;
            $hreflang[$languageCode] = URL::to(LocaleUrl::for($languageCode, $path));
        }

        $seo = SeoProps::for(
            title: $translation->meta_title ?? $translation->title,
            description: $translation->meta_description,
            canonical: url()->current(),
            hreflang: $hreflang,
            ogType: 'article',
            ogImage: $post->featured_image ? URL::to($post->featured_image) : null,
        );

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $translation->title,
            'datePublished' => $translation->published_at?->toIso8601String(),
            'image' => $post->featured_image ? [URL::to($post->featured_image)] : [],
            'inLanguage' => app()->getLocale(),
        ];

        return Inertia::render('public/post-show', array_merge(
            PublicLayoutProps::base(),
            [
                'post' => $translation->load('post.type'),
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
}
