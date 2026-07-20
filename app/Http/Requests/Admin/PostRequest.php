<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        if ($post instanceof Post) {
            return $this->user()?->can('update', $post) ?? false;
        }

        return $this->user()?->can('create', Post::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type_id' => ['required', 'integer', 'exists:content_types,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.body' => ['nullable', 'string'],
            'translations.*.status' => ['required', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'translations.*.published_at' => ['nullable', 'date'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:255'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Pastikan minimal entri bahasa default terisi judulnya — bahasa lain opsional.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var list<array{language_id?: int|string, title?: ?string}> $translations */
            $translations = (array) $this->input('translations', []);
            $defaultLanguageId = Language::defaultModel()->id;

            $hasDefault = collect($translations)->contains(
                fn (array $translation): bool => (int) ($translation['language_id'] ?? 0) === $defaultLanguageId
                    && filled($translation['title'] ?? null),
            );

            if (! $hasDefault) {
                $validator->errors()->add('translations', 'Bahasa default wajib diisi (judul).');
            }
        });
    }
}
