<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\PostTranslation;
use Illuminate\Http\Request;
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

        return Inertia::render('public/post-archive', [
            'contentType' => [
                'slug' => $contentType->slug,
                'name' => $contentType->translate()?->name ?? ucfirst($contentType->slug),
            ],
            'posts' => $posts,
        ]);
    }

    /**
     * Single post (translation published untuk locale aktif).
     */
    public function show(Request $request, ContentType $contentType, PostTranslation $translation): Response
    {
        return Inertia::render('public/post-show', [
            'post' => $translation->load('post.type'),
            'contentType' => [
                'slug' => $contentType->slug,
                'name' => $contentType->translate()?->name ?? ucfirst($contentType->slug),
            ],
        ]);
    }
}
