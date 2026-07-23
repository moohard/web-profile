<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\WritingStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WritingStyleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access-system') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $writingStyle = $this->route('writingStyle');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(WritingStyle::class, 'name')->ignore($writingStyle),
            ],
            'prompt' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
