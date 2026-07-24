<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\RatingCriterion;
use Illuminate\Foundation\Http\FormRequest;

class RatingCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $criterion = $this->route('ratingCriterion');

        if ($criterion instanceof RatingCriterion) {
            return $this->user()?->can('update', $criterion) ?? false;
        }

        return $this->user()?->can('create', RatingCriterion::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'distinct', 'exists:languages,id'],
            'translations.*.name' => ['required', 'string', 'max:255'],
        ];
    }
}
