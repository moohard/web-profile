<?php

declare(strict_types=1);

namespace App\Actions\Categories;

use App\Models\Category;
use App\Support\ContentSlug;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCategory
{
    /**
     * @param  array{
     *     slug?: null|string,
     *     parent_id?: null|int,
     *     sort_order?: int,
     *     translations: list<array{language_id: int, name: string}>
     * }  $data
     */
    public function __invoke(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $lockedCategories = Category::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'parent_id']);

            $this->ensureNoCycle(
                $lockedCategories,
                $category->id,
                $data['parent_id'] ?? null,
            );

            $slugSource = $data['slug'] ?? $data['translations'][0]['name'];

            $category->update([
                'slug' => ContentSlug::unique(Category::class, $slugSource, $category->id),
                'parent_id' => $data['parent_id'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            foreach ($data['translations'] as $translation) {
                $category->translations()->updateOrCreate(
                    ['language_id' => $translation['language_id']],
                    ['name' => $translation['name']],
                );
            }

            return $category->refresh();
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function ensureNoCycle(
        Collection $categories,
        int $categoryId,
        ?int $parentId,
    ): void {
        $categoriesById = $categories->keyBy('id');
        $visited = [];
        $candidateId = $parentId;

        while ($candidateId !== null && ! isset($visited[$candidateId])) {
            if ($candidateId === $categoryId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Induk kategori tidak boleh membentuk siklus.',
                ]);
            }

            $visited[$candidateId] = true;
            $candidate = $categoriesById->get($candidateId);
            $candidateId = $candidate instanceof Category
                ? $candidate->parent_id
                : null;
        }
    }
}
