<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Models\Language;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Support\ContentSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Daftar tag (dikelola inline — tanpa halaman create/edit terpisah).
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Tag::class);

        $tags = Tag::query()
            ->with('translations')
            ->orderBy('slug')
            ->get()
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'translations' => $tag->translations
                    ->map(fn (TagTranslation $t): array => [
                        'language_id' => $t->language_id,
                        'name' => $t->name,
                    ])
                    ->all(),
            ]);

        return Inertia::render('admin/tags/index', [
            'tags' => $tags,
            'languages' => Language::active()
                ->get(['id', 'code', 'name'])
                ->map(fn (Language $lang): array => [
                    'id' => $lang->id,
                    'code' => $lang->code,
                    'name' => $lang->name,
                ])
                ->all(),
        ]);
    }

    /**
     * Simpan tag baru + translation per bahasa.
     */
    public function store(TagRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $tag = Tag::create([
                'slug' => ContentSlug::unique(Tag::class, $slugSource),
            ]);

            $this->syncTranslations($tag, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil dibuat.']);

        return back();
    }

    /**
     * Perbarui tag + translation per bahasa.
     */
    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $tag): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $tag->update([
                'slug' => ContentSlug::unique(Tag::class, $slugSource, $tag->id),
            ]);

            $this->syncTranslations($tag, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil diperbarui.']);

        return back();
    }

    /**
     * Hapus tag — ditolak bila masih terhubung ke post.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        if ($tag->posts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Tag tidak bisa dihapus karena masih terhubung ke post.']);

            return back();
        }

        $tag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tag berhasil dihapus.']);

        return back();
    }

    /**
     * Upsert translation per bahasa untuk tag.
     *
     * @param  list<array{language_id: int, name: string}>  $translations
     */
    private function syncTranslations(Tag $tag, array $translations): void
    {
        foreach ($translations as $translation) {
            $tag->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                ['name' => $translation['name']],
            );
        }
    }
}
