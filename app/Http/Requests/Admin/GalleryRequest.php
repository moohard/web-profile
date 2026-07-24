<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Gallery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $gallery = $this->route('gallery');

        if ($gallery instanceof Gallery) {
            return $this->user()?->can('update', $gallery) ?? false;
        }

        return $this->user()?->can('create', Gallery::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('galleries', 'slug')->ignore($this->route('gallery'))],
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id', 'distinct'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'images' => ['required', 'array'],
            'images.*.id' => ['nullable', 'integer'],
            'images.*.path' => ['required', 'string', 'max:255'],
            'images.*.captions' => ['required', 'array'],
            'images.*.captions.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'images.*.captions.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
