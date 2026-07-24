<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Menus\SyncMenuItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuItemRequest;
use App\Http\Requests\Admin\MenuItemSyncRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class MenuItemController extends Controller
{
    public function store(MenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->itemData();
        $parentId = $data['parent_id'];

        if ($parentId !== null) {
            $parent = $menu->allItems()->find($parentId);

            if (! $parent instanceof MenuItem || $parent->parent_id !== null) {
                return back()->withErrors(['parent_id' => 'Item menu hanya mendukung maksimal dua tingkat.']);
            }
        }

        $item = $menu->allItems()->create([
            'parent_id' => $parentId,
            'link_type' => $data['link_type'],
            'link_ref' => $data['link_ref'],
            'url' => $data['url'],
            'sort_order' => $data['sort_order'],
        ]);

        foreach ($data['translations'] as $translation) {
            $item->translations()->create($translation);
        }

        PublicLayoutProps::flushCache();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item menu berhasil dibuat.']);

        return back();
    }

    public function sync(MenuItemSyncRequest $request, Menu $menu, SyncMenuItems $syncMenuItems): RedirectResponse
    {
        $syncMenuItems($menu, $request->items());
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item menu berhasil diperbarui.']);

        return back();
    }
}
