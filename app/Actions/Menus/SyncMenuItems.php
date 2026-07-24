<?php

declare(strict_types=1);

namespace App\Actions\Menus;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncMenuItems
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function __invoke(Menu $menu, array $items): void
    {
        DB::transaction(function () use ($menu, $items): void {
            $existingItems = $menu->allItems()->with('translations')->orderBy('id')->lockForUpdate()->get();
            $existingById = $existingItems->keyBy('id');
            $submittedIds = [];

            foreach ($items as $index => $item) {
                $id = $item['id'] ?? null;

                if ($id !== null && ! $existingById->has((int) $id)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id" => 'Item menu tidak ditemukan pada menu ini.',
                    ]);
                }

                if ($id !== null) {
                    $submittedIds[] = (int) $id;
                }
            }

            $this->ensureValidHierarchy($existingItems, $items, $submittedIds);

            foreach ($items as $item) {
                $id = $item['id'] ?? null;
                $attributes = [
                    'parent_id' => $item['parent_id'] ?? null,
                    'link_type' => $item['link_type'],
                    'link_ref' => $item['link_ref'] ?? null,
                    'url' => $item['url'] ?? null,
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                ];

                $menuItem = $id !== null
                    ? $existingById->get((int) $id)
                    : $menu->allItems()->create($attributes);

                if ($id !== null && $menuItem instanceof MenuItem) {
                    $menuItem->update($attributes);
                }

                if (! $menuItem instanceof MenuItem) {
                    continue;
                }

                foreach ($item['translations'] as $translation) {
                    $menuItem->translations()->updateOrCreate(
                        ['language_id' => (int) $translation['language_id']],
                        ['label' => (string) $translation['label']],
                    );
                }
            }

            $existingItems
                ->reject(fn (MenuItem $item): bool => in_array($item->id, $submittedIds, true))
                ->each
                ->delete();
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, MenuItem>  $existingItems
     * @param  list<array<string, mixed>>  $items
     * @param  list<int>  $submittedIds
     */
    private function ensureValidHierarchy(Collection $existingItems, array $items, array $submittedIds): void
    {
        $existingById = $existingItems->keyBy('id');
        $parentById = [];

        foreach ($items as $index => $item) {
            $id = $item['id'] ?? null;
            $parentId = $item['parent_id'] ?? null;

            if ($parentId !== null && ! in_array((int) $parentId, $submittedIds, true) && ! $existingById->has((int) $parentId)) {
                throw ValidationException::withMessages([
                    "items.{$index}.parent_id" => 'Induk item harus berada pada menu yang sama.',
                ]);
            }

            if ($id !== null) {
                $parentById[(int) $id] = $parentId !== null ? (int) $parentId : null;
            }
        }

        foreach ($parentById as $itemId => $parentId) {
            $visited = [];
            $depth = 0;
            $candidateId = $parentId;

            while ($candidateId !== null && ! isset($visited[$candidateId])) {
                if ($candidateId === $itemId) {
                    throw ValidationException::withMessages([
                        "items.{$this->itemIndex($items, $itemId)}.parent_id" => 'Induk item tidak boleh membentuk siklus.',
                    ]);
                }

                $visited[$candidateId] = true;
                $depth++;

                if ($depth > 1) {
                    throw ValidationException::withMessages([
                        "items.{$this->itemIndex($items, $itemId)}.parent_id" => 'Item menu hanya mendukung maksimal dua tingkat.',
                    ]);
                }

                $candidateId = $parentById[$candidateId] ?? $existingById->get($candidateId)?->parent_id;
            }
        }
    }

    /** @param list<array<string, mixed>> $items */
    private function itemIndex(array $items, int $itemId): int
    {
        foreach ($items as $index => $item) {
            if (($item['id'] ?? null) === $itemId) {
                return $index;
            }
        }

        return 0;
    }
}
