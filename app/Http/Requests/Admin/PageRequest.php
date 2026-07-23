<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PageMode;
use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Page;
use App\Support\Pages\PageTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('page');

        $authorized = $page instanceof Page
            ? $this->user()?->can('update', $page) ?? false
            : $this->user()?->can('create', Page::class) ?? false;

        if (! $authorized) {
            return false;
        }

        if ($this->input('mode') === PageMode::Code->value) {
            return Gate::forUser($this->user())->allows('use-page-code-mode');
        }

        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in([PageMode::Code->value, PageMode::Template->value])],
            'template_key' => ['required', 'string', Rule::in(PageTemplateRegistry::keys())],
            'hero_enabled' => ['required', 'boolean'],
            'hero_image' => ['nullable', 'string', 'max:2048'],
            'sidebar_enabled' => ['required', 'boolean'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.status' => ['required', Rule::in([PostStatus::Draft->value, PostStatus::Published->value])],
            'translations.*.hero_heading' => ['nullable', 'string', 'max:255'],
            'translations.*.hero_subheading' => ['nullable', 'string', 'max:255'],
            'translations.*.hero_cta_text' => ['nullable', 'string', 'max:255'],
            'translations.*.hero_cta_link' => ['nullable', 'string', 'max:2048'],
            'translations.*.meta_title' => ['nullable', 'string', 'max:60'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:160'],
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

            foreach ($translations as $index => $translation) {
                if (($translation['status'] ?? null) !== PostStatus::Published->value) {
                    continue;
                }

                if (! filled($translation['content'] ?? null)) {
                    $validator->errors()->add(
                        "translations.{$index}.content",
                        'Konten wajib diisi untuk translation Published.',
                    );
                }
            }
        });
    }
}
