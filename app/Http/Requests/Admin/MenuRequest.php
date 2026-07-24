<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        $menu = $this->route('menu');

        if ($menu instanceof Menu) {
            return $this->user()?->can('update', $menu) ?? false;
        }

        return $this->user()?->can('create', Menu::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', Rule::enum(MenuLocation::class)],
        ];
    }
}
