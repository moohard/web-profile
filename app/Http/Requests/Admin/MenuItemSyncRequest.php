<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LinkType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        $menu = $this->route('menu');

        return $menu instanceof Menu && ($this->user()?->can('update', $menu) ?? false);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable', 'integer', Rule::exists(MenuItem::class, 'id')],
            'items.*.parent_id' => ['nullable', 'integer', Rule::exists(MenuItem::class, 'id')],
            'items.*.link_type' => ['required', Rule::enum(LinkType::class)],
            'items.*.link_ref' => ['nullable', 'string', 'max:255'],
            'items.*.url' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'items.*.translations' => ['required', 'array', 'min:1'],
            'items.*.translations.*.language_id' => ['required', 'integer', Rule::exists('languages', 'id')],
            'items.*.translations.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        $items = $this->validated('items');

        return is_array($items) ? array_values($items) : [];
    }
}
