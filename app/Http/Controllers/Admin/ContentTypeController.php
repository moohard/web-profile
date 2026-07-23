<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentTypeRequest;
use App\Models\ContentType;
use App\Models\ContentTypeTranslation;
use App\Models\Language;
use App\Models\WritingStyle;
use App\Support\ContentSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContentTypeController extends Controller
{
    /**
     * Daftar jenis konten.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ContentType::class);

        $contentTypes = ContentType::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContentType $contentType): array => $this->toArray($contentType));

        return Inertia::render('admin/content-types/index', [
            'contentTypes' => $contentTypes,
            'languages' => $this->languageOptions(),
        ]);
    }

    /**
     * Form pembuatan jenis konten baru.
     */
    public function create(): Response
    {
        $this->authorize('create', ContentType::class);

        return Inertia::render('admin/content-types/form', [
            'contentType' => null,
            'languages' => $this->languageOptions(),
            'writingStyles' => $this->writingStyleOptions(),
        ]);
    }

    /**
     * Simpan jenis konten baru + translation per bahasa.
     */
    public function store(ContentTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $contentType = ContentType::create([
                'slug' => ContentSlug::unique(ContentType::class, $slugSource),
                'icon' => $data['icon'] ?? null,
                'writing_style_id' => $data['writing_style_id'] ?? null,
                'archive_template_key' => $data['archive_template_key'] ?? 'default',
                'single_template_key' => $data['single_template_key'] ?? 'default',
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncTranslations($contentType, $data['translations']);
        });

        $this->bustCaches();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis konten berhasil dibuat.']);

        return redirect()->route('admin.content-types.index');
    }

    /**
     * Form perubahan jenis konten.
     */
    public function edit(ContentType $contentType): Response
    {
        $this->authorize('update', $contentType);

        $contentType->load('translations');

        return Inertia::render('admin/content-types/form', [
            'contentType' => $this->toArray($contentType),
            'languages' => $this->languageOptions(),
            'writingStyles' => $this->writingStyleOptions(),
        ]);
    }

    /**
     * Perbarui jenis konten + translation per bahasa.
     */
    public function update(ContentTypeRequest $request, ContentType $contentType): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $contentType): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $contentType->update([
                'slug' => ContentSlug::unique(ContentType::class, $slugSource, $contentType->id),
                'icon' => $data['icon'] ?? null,
                'writing_style_id' => $data['writing_style_id'] ?? null,
                'archive_template_key' => $data['archive_template_key'] ?? 'default',
                'single_template_key' => $data['single_template_key'] ?? 'default',
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncTranslations($contentType, $data['translations']);
        });

        $this->bustCaches();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis konten berhasil diperbarui.']);

        return redirect()->route('admin.content-types.index');
    }

    /**
     * Hapus jenis konten — ditolak bila masih memiliki post.
     */
    public function destroy(ContentType $contentType): RedirectResponse
    {
        $this->authorize('delete', $contentType);

        // withTrashed(): post yang sudah di-soft-delete pun MENGUNCI penghapusan
        // (sampai di-forceDelete) — posts.type_id cascadeOnDelete, jadi lolos di
        // sini berarti post trashed ikut HARD-DELETE bypass forceDelete/policy.
        if ($contentType->posts()->withTrashed()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Jenis konten tidak bisa dihapus karena masih memiliki post.']);

            return back();
        }

        $contentType->delete();

        $this->bustCaches();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis konten berhasil dihapus.']);

        return back();
    }

    /**
     * Upsert translation per bahasa untuk jenis konten.
     *
     * @param  list<array{language_id: int, name: string, description?: ?string}>  $translations
     */
    private function syncTranslations(ContentType $contentType, array $translations): void
    {
        foreach ($translations as $translation) {
            $contentType->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                [
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null,
                ],
            );
        }
    }

    /**
     * Bust cache sidebar (per-locale) & layout publik (per-bahasa) — content types
     * dipakai untuk membangun menu dinamis yang di-cache selama 1 jam.
     */
    private function bustCaches(): void
    {
        foreach (Language::active()->get() as $language) {
            Cache::forget('inertia.content_types.'.$language->code);
            Cache::forget('public_layout.'.$language->id);
        }
    }

    /**
     * @return array{id: int, slug: string, icon: ?string, writing_style_id: ?int, archive_template_key: string, single_template_key: string, is_active: bool, sort_order: int, translations: array<int, array{language_id: int, name: string, description: ?string}>}
     */
    private function toArray(ContentType $contentType): array
    {
        return [
            'id' => $contentType->id,
            'slug' => $contentType->slug,
            'icon' => $contentType->icon,
            'writing_style_id' => $contentType->writing_style_id,
            'archive_template_key' => $contentType->archive_template_key,
            'single_template_key' => $contentType->single_template_key,
            'is_active' => $contentType->is_active,
            'sort_order' => $contentType->sort_order,
            'translations' => $contentType->translations
                ->map(fn (ContentTypeTranslation $t): array => [
                    'language_id' => $t->language_id,
                    'name' => $t->name,
                    'description' => $t->description,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, array{id: int, code: string, name: string}>
     */
    private function languageOptions(): array
    {
        return Language::active()
            ->get(['id', 'code', 'name'])
            ->map(fn (Language $lang): array => [
                'id' => $lang->id,
                'code' => $lang->code,
                'name' => $lang->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function writingStyleOptions(): array
    {
        return WritingStyle::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (WritingStyle $style): array => [
                'id' => $style->id,
                'name' => $style->name,
            ])
            ->values()
            ->all();
    }
}
