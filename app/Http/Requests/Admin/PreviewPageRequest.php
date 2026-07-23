<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PageMode;
use App\Models\Page;
use App\Support\Pages\PageTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PreviewPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->can('create', Page::class)) {
            return false;
        }

        return $this->input('mode') !== PageMode::Code->value
            || Gate::forUser($user)->allows('use-page-code-mode');
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(PageMode::class)],
            'template_key' => ['required', Rule::in(PageTemplateRegistry::keys())],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ];
    }
}
