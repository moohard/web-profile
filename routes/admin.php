<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AiConfigController;
use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContentTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Placeholder routes — diisi di fase berikutnya
Route::get('/posts', fn () => Inertia::render('admin/placeholder', ['section' => 'Konten']))->name('posts.index');
Route::get('/pages', fn () => Inertia::render('admin/placeholder', ['section' => 'Halaman']))->name('pages.index');
Route::get('/menus', fn () => Inertia::render('admin/placeholder', ['section' => 'Menu']))->name('menus.index')->middleware('permission:admin.access-appearance');
Route::get('/widgets', fn () => Inertia::render('admin/placeholder', ['section' => 'Widget']))->name('widgets.index')->middleware('permission:admin.access-appearance');
Route::get('/contact-messages', fn () => Inertia::render('admin/placeholder', ['section' => 'Pesan Kontak']))->name('contact-messages.index');
Route::get('/testimonials', fn () => Inertia::render('admin/placeholder', ['section' => 'Testimoni']))->name('testimonials.index');
Route::get('/ratings', fn () => Inertia::render('admin/placeholder', ['section' => 'Penilaian']))->name('ratings.index');
Route::get('/users', fn () => Inertia::render('admin/placeholder', ['section' => 'Pengguna']))->name('users.index')->middleware('permission:admin.access-system');
Route::get('/settings', fn () => Inertia::render('admin/placeholder', ['section' => 'Pengaturan']))->name('settings.index')->middleware('permission:admin.access-system');
Route::get('/settings/ai', [AiConfigController::class, 'index'])
    ->middleware('permission:admin.access-system')
    ->name('settings.ai');
Route::put('/settings/ai/{task}', [AiConfigController::class, 'update'])
    ->middleware('permission:admin.access-system')
    ->name('settings.ai.update');
Route::get('/settings/languages', fn () => Inertia::render('admin/placeholder', ['section' => 'Bahasa']))->name('settings.languages')->middleware('permission:admin.access-system');
Route::get('/content-types', [ContentTypeController::class, 'index'])->name('content-types.index');
Route::get('/content-types/create', [ContentTypeController::class, 'create'])->name('content-types.create');
Route::post('/content-types', [ContentTypeController::class, 'store'])->name('content-types.store');
Route::get('/content-types/{contentType}/edit', [ContentTypeController::class, 'edit'])->name('content-types.edit');
Route::put('/content-types/{contentType}', [ContentTypeController::class, 'update'])->name('content-types.update');
Route::delete('/content-types/{contentType}', [ContentTypeController::class, 'destroy'])->name('content-types.destroy');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::get('/galleries', fn () => Inertia::render('admin/placeholder', ['section' => 'Galeri']))->name('galleries.index');
Route::get('/writing-styles', fn () => Inertia::render('admin/placeholder', ['section' => 'Gaya Bahasa']))->name('writing-styles.index')->middleware('permission:admin.access-system');
Route::get('/rating-criteria', fn () => Inertia::render('admin/placeholder', ['section' => 'Kriteria Penilaian']))->name('rating-criteria.index')->middleware('permission:admin.access-system');
Route::get('/media', [MediaController::class, 'index'])->name('media.index');
Route::post('/media', [MediaController::class, 'store'])
    ->middleware('permission:media.create')
    ->name('media.store');
Route::patch('/media/{media}', [MediaController::class, 'update'])
    ->middleware('permission:media.update')
    ->name('media.update');
Route::delete('/media/{media}', [MediaController::class, 'destroy'])
    ->middleware('permission:media.delete')
    ->name('media.destroy');

Route::post('/ai/translate', [AiController::class, 'translate'])
    ->middleware(['permission:ai.create', 'throttle:30,1'])
    ->name('ai.translate');
Route::post('/ai/apply-translation', [AiController::class, 'applyTranslation'])
    ->middleware(['permission:ai.update', 'throttle:30,1'])
    ->name('ai.apply-translation');
