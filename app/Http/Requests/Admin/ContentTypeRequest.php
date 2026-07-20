<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;

class ContentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contentType = $this->route('contentType');

        if ($contentType instanceof ContentType) {
            return $this->user()?->can('update', $contentType) ?? false;
        }

        return $this->user()?->can('create', ContentType::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'writing_style_id' => ['nullable', 'integer', 'exists:writing_styles,id'],
            'archive_template_key' => ['nullable', 'string', 'max:255'],
            'single_template_key' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ];
    }
}
