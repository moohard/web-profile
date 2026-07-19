<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PostTranslation;
use App\Support\LocaleUrl;
use App\Support\PublicLayoutProps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Halaman beranda publik: daftar post terbaru untuk locale aktif.
     */
    public function index(Request $request): Response
    {
        $langId = Language::current()->id;

        $latest = PostTranslation::query()
            ->with('post.type')
            ->where('language_id', $langId)
            ->published()
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => config('app.name'),
                    'url' => url('/'),
                ],
                [
                    '@type' => 'WebSite',
                    'name' => config('app.name'),
                    'url' => url('/'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => url('/search?q={query}'),
                        'query-input' => 'required name=query',
                    ],
                ],
            ],
        ];

        $latestPosts = $latest->map(fn (PostTranslation $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'url' => LocaleUrl::for(app()->getLocale(), '/'.$t->post->type->slug.'/'.$t->slug),
        ])->values()->all();

        return Inertia::render('public/home', array_merge(
            PublicLayoutProps::base(),
            [
                'region' => PublicLayoutProps::region(),
                'latestPosts' => $latestPosts,
                'jsonLd' => $jsonLd,
            ],
        ));
    }
}
