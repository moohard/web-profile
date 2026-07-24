<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuRequest;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemTranslation;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Menu::class);

        return Inertia::render('admin/menus/index', [
            'menus' => Menu::query()
                ->with(['items.children', 'items.translations', 'items.children.translations'])
                ->orderBy('location')
                ->orderBy('name')
                ->get()
                ->map(fn (Menu $menu): array => $this->menuData($menu))
                ->all(),
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

    public function store(MenuRequest $request): RedirectResponse
    {
        Menu::create($request->validated());
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Menu berhasil dibuat.']);

        return back();
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->validated());
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Menu berhasil diperbarui.']);

        return back();
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);
        $menu->delete();
        PublicLayoutProps::flushCache();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Menu berhasil dihapus.']);

        return back();
    }

    /** @return array<string, mixed> */
    private function menuData(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'location' => $menu->location->value,
            'items' => $menu->items->map(fn (MenuItem $item): array => $this->itemData($item))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function itemData(MenuItem $item): array
    {
        return [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'link_type' => $item->link_type->value,
            'link_ref' => $item->link_ref,
            'url' => $item->url,
            'sort_order' => $item->sort_order,
            'translations' => $item->translations->map(fn (MenuItemTranslation $translation): array => [
                'language_id' => $translation->language_id,
                'label' => $translation->label,
            ])->all(),
            'children' => $item->children->map(fn (MenuItem $child): array => $this->itemData($child))->all(),
        ];
    }
}
