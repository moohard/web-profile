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
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.body' => ['nullable', 'string'],
            'translations.*.status' => ['required', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'translations.*.published_at' => ['nullable', 'date'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:60'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var list<array{language_id?: int|string, title?: ?string, body?: ?string, status?: ?string}> $translations */
            $translations = (array) $this->input('translations', []);
            $defaultLanguageId = Language::defaultModel()->id;

            $defaultTranslationIndex = collect($translations)->search(
                fn (array $translation): bool => (int) ($translation['language_id'] ?? 0) === $defaultLanguageId,
            );

            if ($defaultTranslationIndex === false) {
                $validator->errors()->add('translations', 'Bahasa default wajib diisi (judul).');

                return;
            }

            if (! filled($translations[$defaultTranslationIndex]['title'] ?? null)) {
                $validator->errors()->add(
                    "translations.{$defaultTranslationIndex}.title",
                    'Judul bahasa default wajib diisi.',
                );
            }

            foreach ($translations as $index => $translation) {
                if (($translation['status'] ?? PostStatus::Draft->value) !== PostStatus::Published->value) {
                    continue;
                }

                if (! filled($translation['title'] ?? null)) {
                    $validator->errors()->add(
                        "translations.{$index}.title",
                        'Judul wajib diisi untuk translation Published.',
                    );
                }

                if (! filled($translation['body'] ?? null)) {
                    $validator->errors()->add(
                        "translations.{$index}.body",
                        'Konten wajib diisi untuk translation Published.',
                    );
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $defaultLanguageId = Language::defaultModel()->id;
        $translations = [];

        foreach ((array) $this->input('translations', []) as $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $translation['status'] ??= PostStatus::Draft->value;

            $isDefault = (int) ($translation['language_id'] ?? 0) === $defaultLanguageId;
            $isEmptyDraft = $translation['status'] === PostStatus::Draft->value
                && ! collect([
                    $translation['title'] ?? null,
                    $translation['slug'] ?? null,
                    $translation['body'] ?? null,
                    $translation['published_at'] ?? null,
                    $translation['meta_title'] ?? null,
                    $translation['meta_description'] ?? null,
                ])->contains(fn (mixed $value): bool => filled($value));

            if (! $isDefault && $isEmptyDraft) {
                continue;
            }

            $translations[] = $translation;
        }

        $this->merge(['translations' => $translations]);
    }
}
