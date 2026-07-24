<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Http\Controllers\Public\RatingController;
use App\Http\Controllers\Public\TestimonialController;
use App\Support\PublicPathResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Dispatch path publik (tanpa prefix locale) lewat single PublicPathResolver.
 *
 * @return mixed
 */
$dispatchPublicPath = function (Request $request) {
    $path = (string) $request->attributes->get('public_path', '');

    if ($path === '') {
        return app(HomeController::class)->index($request);
    }

    $resolved = PublicPathResolver::resolve($path);

    return match ($resolved['kind']) {
        'archive' => app(PostController::class)->archive(request(), $resolved['contentType']),
        'single' => app(PostController::class)->show(
            request(),
            $resolved['contentType'],
            $resolved['translation'],
        ),
        'page' => app(PageController::class)->show($resolved['translation']),
        default => abort(404),
    };
};

// Segment pertama yang tidak boleh tertangkap catch-all publik (rute sistem).
// Pola dipakai pada parameter catch-all publicPath — negative lookahead.
// Pakai (?:/|$) bukan $ saja: di compiled regex Symfony, $ merujuk ke akhir path
// penuh, sehingga /admin/posts lolos ke catch-all (admin diikuti /posts, bukan end).
$reserved = 'admin|login|logout|register|dashboard|settings|password|user|email|two-factor|sanctum|up';
$publicPath = '(?!(?:'.$reserved.')(?:/|$))[a-z0-9\-]+(?:/[a-z0-9\-]+){0,2}';

// ── Sitemap (file statis di public/; rute agar tersedia di testing & tanpa static server) ──
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    abort_unless(is_file($path), 404);
    $contents = file_get_contents($path);

    abort_unless(is_string($contents), 404);

    return response($contents, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})->name('sitemap');

// ── Beranda (locale default, tanpa prefix) ──
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/kontak', [ContactController::class, 'store'])
    ->middleware('throttle:contact-submit')
    ->name('contact.store');
Route::post('/testimoni', [TestimonialController::class, 'store'])
    ->middleware('throttle:testimonial-submit')
    ->name('testimonial.store');
Route::post('/rating', [RatingController::class, 'store'])
    ->middleware('throttle:rating-submit')
    ->name('rating.store');
// ── Catch-all publik: maksimal 2 segment konten + 1 prefix locale ──
// Locale divalidasi dari tabel languages oleh SetLocale. Prefix bahasa default
// diarahkan canonical, sedangkan bahasa inactive berakhir sebagai 404.
Route::get('/{publicPath}', $dispatchPublicPath)
    ->where('publicPath', $publicPath)
    ->name('public.resolve');

// Dashboard (auth) — path statis
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
