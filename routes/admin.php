<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AiConfigController;
use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContentTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PagePreviewController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\RatingController;
use App\Http\Controllers\Admin\RatingCriterionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TemplateRegistryController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\WritingStyleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('contact-messages')->name('contact-messages.')->group(function (): void {
    Route::get('/', [ContactMessageController::class, 'index'])->name('index');
    Route::get('/{contactMessage}', [ContactMessageController::class, 'show'])->name('show');
    Route::put('/{contactMessage}', [ContactMessageController::class, 'update'])->name('update');
    Route::delete('/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('destroy');
});

Route::prefix('posts')->name('posts.')->group(function (): void {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/trash', [PostController::class, 'trash'])->name('trash');
    Route::get('/create', [PostController::class, 'create'])->name('create');
    Route::post('/', [PostController::class, 'store'])->name('store');
    Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
    Route::put('/{post}', [PostController::class, 'update'])->name('update');
    Route::patch('/{post}/restore', [PostController::class, 'restore'])->name('restore')->withTrashed();
    Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
    Route::delete('/{post}/force-delete', [PostController::class, 'forceDelete'])->withTrashed()->name('force-delete');
});

Route::prefix('pages')->name('pages.')->group(function (): void {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/trash', [PageController::class, 'trash'])->name('trash');
    Route::get('/create', [PageController::class, 'create'])->name('create');
    Route::post('/preview', PagePreviewController::class)->name('preview');
    Route::post('/', [PageController::class, 'store'])->name('store');
    Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    Route::put('/{page}', [PageController::class, 'update'])->name('update');
    Route::patch('/{page}/restore', [PageController::class, 'restore'])->name('restore')->withTrashed();
    Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
    Route::delete('/{page}/force-delete', [PageController::class, 'forceDelete'])->withTrashed()->name('force-delete');
});

Route::prefix('menus')->name('menus.')->middleware('permission:admin.access-appearance')->group(function (): void {
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::post('/', [MenuController::class, 'store'])->name('store');
    Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
    Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
    Route::post('/{menu}/items', [MenuItemController::class, 'store'])->name('items.store');
    Route::put('/{menu}/items/sync', [MenuItemController::class, 'sync'])->name('items.sync');
});
Route::resource('widgets', WidgetController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('permission:admin.access-appearance');
Route::prefix('testimonials')->name('testimonials.')->group(function (): void {
    Route::get('/', [TestimonialController::class, 'index'])->name('index');
    Route::patch('/{testimonial}/approve', [TestimonialController::class, 'approve'])->name('approve');
    Route::put('/reorder', [TestimonialController::class, 'reorder'])->name('reorder');
    Route::delete('/{testimonial}', [TestimonialController::class, 'destroy'])->name('destroy');
});
Route::get('/ratings', [RatingController::class, 'index'])
    ->middleware('permission:ratings.viewAny')
    ->name('ratings.index');
Route::resource('users', UserController::class)->middleware('permission:admin.access-system');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('permission:admin.access-system');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('permission:admin.access-system');
Route::get('/settings/ai', [AiConfigController::class, 'index'])
    ->middleware('permission:admin.access-system')
    ->name('settings.ai');
Route::put('/settings/ai/{task}', [AiConfigController::class, 'update'])
    ->middleware('permission:admin.access-system')
    ->name('settings.ai.update');
Route::prefix('/settings/languages')
    ->name('settings.languages.')
    ->middleware('permission:admin.access-system')
    ->group(function (): void {
        Route::get('/', [LanguageController::class, 'index'])->name('index');
        Route::post('/', [LanguageController::class, 'store'])->name('store');
        Route::put('/{language}', [LanguageController::class, 'update'])->name('update');
        Route::delete('/{language}', [LanguageController::class, 'destroy'])->name('destroy');
    });
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
Route::post('/tags/quick-store', [TagController::class, 'quickStore'])
    ->name('tags.quick-store');
Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
Route::prefix('/galleries')->name('galleries.')->group(function (): void {
    Route::get('/', [GalleryController::class, 'index'])->name('index');
    Route::get('/create', [GalleryController::class, 'create'])->name('create');
    Route::post('/', [GalleryController::class, 'store'])->name('store');
    Route::get('/{gallery}/edit', [GalleryController::class, 'edit'])->name('edit');
    Route::put('/{gallery}', [GalleryController::class, 'update'])->name('update');
    Route::delete('/{gallery}', [GalleryController::class, 'destroy'])->name('destroy');
});
Route::prefix('/writing-styles')
    ->name('writing-styles.')
    ->middleware('permission:admin.access-system')
    ->group(function (): void {
        Route::get('/', [WritingStyleController::class, 'index'])->name('index');
        Route::post('/', [WritingStyleController::class, 'store'])->name('store');
        Route::put('/{writingStyle}', [WritingStyleController::class, 'update'])->name('update');
        Route::delete('/{writingStyle}', [WritingStyleController::class, 'destroy'])->name('destroy');
    });
Route::prefix('/rating-criteria')->name('rating-criteria.')->group(function (): void {
    Route::get('/', [RatingCriterionController::class, 'index'])->name('index');
    Route::post('/', [RatingCriterionController::class, 'store'])->name('store');
    Route::put('/{ratingCriterion}', [RatingCriterionController::class, 'update'])->name('update');
    Route::delete('/{ratingCriterion}', [RatingCriterionController::class, 'destroy'])->name('destroy');
});
Route::get('/templates', [TemplateRegistryController::class, 'index'])
    ->middleware('permission:admin.access-system')
    ->name('templates.index');
Route::get('/media', [MediaController::class, 'index'])->name('media.index');
Route::get('/media/picker', [MediaController::class, 'picker'])
    ->middleware('permission:media.viewAny')
    ->name('media.picker');
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
Route::post('/ai/refine', [AiController::class, 'refine'])
    ->middleware(['permission:ai.update', 'throttle:30,1'])
    ->name('ai.refine');
Route::post('/ai/markup-conform', [AiController::class, 'markupConform'])
    ->middleware(['permission:ai.update', 'throttle:30,1'])
    ->name('ai.markup-conform');
