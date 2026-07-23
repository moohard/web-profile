<?php

declare(strict_types=1);

namespace App\Support\Categories;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryTree
{
    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, array{category: Category, depth: int}>
     */
    public static function flatten(Collection $categories): Collection
    {
        $ordered = $categories
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
        $knownIds = $ordered->pluck('id')->all();
        $children = $ordered->groupBy(
            fn (Category $category): int => $category->parent_id ?? 0,
        );
        $roots = $ordered->filter(
            fn (Category $category): bool => $category->parent_id === null
                || ! in_array($category->parent_id, $knownIds, true),
        );
        $result = collect();
        $visited = [];

        $append = function (Category $category, int $depth) use (&$append, &$visited, $children, $result): void {
            if (isset($visited[$category->id])) {
                return;
            }

            $visited[$category->id] = true;
            $result->push(['category' => $category, 'depth' => $depth]);

            foreach ($children->get($category->id, collect()) as $child) {
                $append($child, $depth + 1);
            }
        };

        foreach ($roots as $root) {
            $append($root, 0);
        }

        foreach ($ordered as $category) {
            $append($category, 0);
        }

        return $result;
    }
}
