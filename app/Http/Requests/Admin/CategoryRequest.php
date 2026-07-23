<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        if ($category instanceof Category) {
            return $this->user()?->can('update', $category) ?? false;
        }

        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{
     *     slug: null|string,
     *     parent_id: null|int,
     *     sort_order: int,
     *     translations: list<array{language_id: int, name: string}>
     * }
     */
    public function validatedCategoryData(): array
    {
        $validated = $this->validated();
        $translations = [];
        $validatedTranslations = $validated['translations'] ?? [];

        if (is_array($validatedTranslations)) {
            foreach ($validatedTranslations as $translation) {
                if (! is_array($translation)) {
                    continue;
                }

                $translations[] = [
                    'language_id' => (int) $translation['language_id'],
                    'name' => (string) $translation['name'],
                ];
            }
        }

        $slug = $validated['slug'] ?? null;
        $parentId = $validated['parent_id'] ?? null;
        $sortOrder = $validated['sort_order'] ?? 0;

        return [
            'slug' => is_string($slug) && $slug !== '' ? $slug : null,
            'parent_id' => $parentId !== null ? (int) $parentId : null,
            'sort_order' => (int) $sortOrder,
            'translations' => $translations,
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $category = $this->route('category');
            $parentId = $this->integer('parent_id');

            if (! $category instanceof Category || $parentId === 0) {
                return;
            }

            $visited = [];
            $candidate = Category::query()->find($parentId);

            while ($candidate !== null && ! isset($visited[$candidate->id])) {
                if ($candidate->id === $category->id) {
                    $validator->errors()->add(
                        'parent_id',
                        'Induk kategori tidak boleh membentuk siklus.',
                    );

                    return;
                }

                $visited[$candidate->id] = true;
                $candidate = $candidate->parent_id !== null
                    ? Category::query()->find($candidate->parent_id)
                    : null;
            }
        }];
    }
}
