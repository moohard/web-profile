<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PostTranslation;
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

        return Inertia::render('public/home', [
            'latestPosts' => $latest,
            'locale' => app()->getLocale(),
        ]);
    }
}
