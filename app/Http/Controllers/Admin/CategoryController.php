<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Support\ContentSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Daftar kategori (dikelola inline — tanpa halaman create/edit terpisah).
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('translations')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'sort_order' => $category->sort_order,
                'translations' => $category->translations
                    ->map(fn (CategoryTranslation $t): array => [
                        'language_id' => $t->language_id,
                        'name' => $t->name,
                    ])
                    ->all(),
            ]);

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
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
     * Simpan kategori baru + translation per bahasa.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $category = Category::create([
                'slug' => ContentSlug::unique(Category::class, $slugSource),
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncTranslations($category, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dibuat.']);

        return back();
    }

    /**
     * Perbarui kategori + translation per bahasa.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $category): void {
            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $category->update([
                'slug' => ContentSlug::unique(Category::class, $slugSource, $category->id),
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->syncTranslations($category, $data['translations']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.']);

        return back();
    }

    /**
     * Hapus kategori — ditolak bila masih punya post terkait.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->posts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kategori tidak bisa dihapus karena masih memiliki post.']);

            return back();
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dihapus.']);

        return back();
    }

    /**
     * Upsert translation per bahasa untuk kategori.
     *
     * @param  list<array{language_id: int, name: string}>  $translations
     */
    private function syncTranslations(Category $category, array $translations): void
    {
        foreach ($translations as $translation) {
            $category->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                ['name' => $translation['name']],
            );
        }
    }
}
