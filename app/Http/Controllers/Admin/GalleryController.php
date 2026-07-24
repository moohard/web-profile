<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Galleries\SyncGalleryImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryRequest;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\GalleryImageTranslation;
use App\Models\GalleryTranslation;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Gallery::class);

        return $this->renderIndex();
    }

    public function create(): Response
    {
        $this->authorize('create', Gallery::class);

        return $this->renderIndex();
    }

    public function store(GalleryRequest $request, SyncGalleryImages $syncGalleryImages): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $syncGalleryImages): void {
            $gallery = Gallery::create([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncTranslations($gallery, $data['translations']);
            $syncGalleryImages($gallery, $data['images']);
        }, attempts: 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri berhasil dibuat.']);

        return to_route('admin.galleries.index');
    }

    public function edit(Gallery $gallery): Response
    {
        $this->authorize('update', $gallery);

        return $this->renderIndex($gallery);
    }

    public function update(
        GalleryRequest $request,
        Gallery $gallery,
        SyncGalleryImages $syncGalleryImages,
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($gallery, $data, $syncGalleryImages): void {
            $lockedGallery = Gallery::query()->lockForUpdate()->findOrFail($gallery->id);
            $lockedGallery->update([
                'slug' => $data['slug'],
                'is_active' => $data['is_active'],
            ]);

            $this->syncTranslations($lockedGallery, $data['translations']);
            $syncGalleryImages($lockedGallery, $data['images']);
        }, attempts: 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri berhasil diperbarui.']);

        return to_route('admin.galleries.index');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);

        $gallery->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Galeri berhasil dihapus.']);

        return to_route('admin.galleries.index');
    }

    private function renderIndex(?Gallery $editingGallery = null): Response
    {
        return Inertia::render('admin/galleries/index', [
            'galleries' => Gallery::query()
                ->with(['translations', 'images.translations'])
                ->latest('id')
                ->get()
                ->map(fn (Gallery $gallery): array => $this->galleryData($gallery))
                ->all(),
            'editingGallery' => $editingGallery === null
                ? null
                : $this->galleryData($editingGallery->load(['translations', 'images.translations'])),
            'languages' => Language::active()
                ->get(['id', 'code', 'name'])
                ->map(fn (Language $language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                ])
                ->all(),
        ]);
    }

    /**
     * @return array{id: int, slug: string, is_active: bool, translations: array<int, array{language_id: int, title: string, description: ?string}>, images: array<int, array{id: int, path: string, sort_order: int, captions: array<int, array{language_id: int, caption: ?string}>}>}
     */
    private function galleryData(Gallery $gallery): array
    {
        return [
            'id' => $gallery->id,
            'slug' => $gallery->slug,
            'is_active' => $gallery->is_active,
            'translations' => $gallery->translations->map(fn (GalleryTranslation $translation): array => [
                'language_id' => $translation->language_id,
                'title' => $translation->title,
                'description' => $translation->description,
            ])->values()->all(),
            'images' => $gallery->images->map(fn (GalleryImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'sort_order' => $image->sort_order,
                'captions' => $image->translations->map(fn (GalleryImageTranslation $translation): array => [
                    'language_id' => $translation->language_id,
                    'caption' => $translation->caption,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<array{language_id: int, title: string, description: ?string}>  $translations
     */
    private function syncTranslations(Gallery $gallery, array $translations): void
    {
        foreach ($translations as $translation) {
            $gallery->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                ['title' => $translation['title'], 'description' => $translation['description']],
            );
        }
    }
}
