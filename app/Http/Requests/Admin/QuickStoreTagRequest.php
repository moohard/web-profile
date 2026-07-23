<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Tag;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickStoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tag::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'language_id' => [
                'required',
                'integer',
                Rule::exists('languages', 'id')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => str($this->input('name'))->squish()->value()]);
        }
    }
}
