<?php

declare(strict_types=1);

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use App\Support\PublicPathResolver;
use Illuminate\Support\Facades\Route;

/**
 * Dispatch path publik (tanpa prefix locale) lewat single PublicPathResolver.
 *
 * Parameter diambil dari route name (bukan positional) supaya aman di bawah
 * prefix {locale} — CallableDispatcher meng-spread parameter berurutan.
 *
 * @return mixed
 */
$dispatchPublicPath = function () {
    $slug1 = (string) request()->route('slug1');
    $slug2 = request()->route('slug2');
    $path = $slug2 ? "{$slug1}/{$slug2}" : $slug1;
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
// Pola dipakai di where('slug1', ...) — negative lookahead.
// Pakai (?:/|$) bukan $ saja: di compiled regex Symfony, $ merujuk ke akhir path
// penuh, sehingga /admin/posts lolos ke catch-all (admin diikuti /posts, bukan end).
$reserved = 'admin|login|logout|register|dashboard|settings|password|user|email|two-factor|sanctum|up';
$publicSlug1 = '(?!(?:'.$reserved.')(?:/|$))[a-z0-9\-]+';

// ── Sitemap (file statis di public/; rute agar tersedia di testing & tanpa static server) ──
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    abort_unless(is_file($path), 404);

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})->name('sitemap');

// ── Beranda (locale default, tanpa prefix) ──
Route::get('/', [HomeController::class, 'index'])->name('home');
// ── Locale non-default ber-prefix (/en, …) — HARUS sebelum catch-all unprefixed
// agar /en tidak tertangkap sebagai slug1. SetLocale (web middleware) set locale
// dari segment-1; path di-resolve dari parameter rute (bukan path request).
//
// Constraint locale HARUS spesifik (bukan [a-z]{2,5}) supaya path seperti
// /admin, /login, /dashboard tidak tertangkap sebagai "locale".
// Seeder Fase 4: non-default aktif = `en`. Tambah kode di sini bila bahasa baru.
Route::prefix('{locale}')
    ->where(['locale' => 'en'])
    ->group(function () use ($dispatchPublicPath, $publicSlug1) {
        Route::get('/', [HomeController::class, 'index'])->name('home.locale');

        Route::get('/{slug1}/{slug2?}', $dispatchPublicPath)
            ->where('slug1', $publicSlug1)
            ->where('slug2', '[a-z0-9\-]+')
            ->name('public.resolve.locale');
    });

// ── Catch-all publik unprefixed: 1 atau 2 segment ──
Route::get('/{slug1}/{slug2?}', $dispatchPublicPath)
    ->where('slug1', $publicSlug1)
    ->where('slug2', '[a-z0-9\-]+')
    ->name('public.resolve');

// Dashboard (auth) — path statis
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
