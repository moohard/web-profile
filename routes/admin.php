<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
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
Route::get('/settings/ai', fn () => Inertia::render('admin/placeholder', ['section' => 'Konfigurasi AI']))->name('settings.ai')->middleware('permission:admin.access-system');
Route::get('/settings/languages', fn () => Inertia::render('admin/placeholder', ['section' => 'Bahasa']))->name('settings.languages')->middleware('permission:admin.access-system');
Route::get('/content-types', fn () => Inertia::render('admin/placeholder', ['section' => 'Jenis Konten']))->name('content-types.index');
Route::get('/categories', fn () => Inertia::render('admin/placeholder', ['section' => 'Kategori']))->name('categories.index');
Route::get('/tags', fn () => Inertia::render('admin/placeholder', ['section' => 'Tag']))->name('tags.index');
Route::get('/galleries', fn () => Inertia::render('admin/placeholder', ['section' => 'Galeri']))->name('galleries.index');
Route::get('/writing-styles', fn () => Inertia::render('admin/placeholder', ['section' => 'Gaya Bahasa']))->name('writing-styles.index')->middleware('permission:admin.access-system');
Route::get('/rating-criteria', fn () => Inertia::render('admin/placeholder', ['section' => 'Kriteria Penilaian']))->name('rating-criteria.index')->middleware('permission:admin.access-system');
// /admin/media akan diisi Fase 7; sementara placeholder
Route::get('/media', fn () => Inertia::render('admin/placeholder', ['section' => 'Media']))->name('media.index');
