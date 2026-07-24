<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\LinkType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer', Rule::exists(MenuItem::class, 'id')],
            'link_type' => ['required', Rule::enum(LinkType::class)],
            'link_ref' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', Rule::exists('languages', 'id')],
            'translations.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, mixed> */
    public function itemData(): array
    {
        $data = $this->validated();

        return [
            'parent_id' => isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            'link_type' => $data['link_type'],
            'link_ref' => $data['link_ref'] ?? null,
            'url' => $data['url'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'translations' => $data['translations'],
        ];
    }
}
