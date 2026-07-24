<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Models\Widget;
use App\Models\WidgetPlacementTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $widget = $this->route('widget');

        if ($widget instanceof Widget) {
            return $this->user()?->can('update', $widget) ?? false;
        }

        return $this->user()?->can('create', Widget::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['HtmlWidget'])],
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id', 'distinct'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'placements' => ['required', 'array', 'min:1'],
            'placements.*.position' => ['required', Rule::enum(WidgetPosition::class)],
            'placements.*.scope' => ['required', Rule::enum(PlacementScope::class)],
            'placements.*.sort_order' => ['required', 'integer', 'min:0', 'max:32767'],
            'placements.*.targets' => ['present', 'array'],
            'placements.*.targets.*.target_type' => [
                'required',
                Rule::in([
                    WidgetPlacementTarget::TYPE_PAGE,
                    WidgetPlacementTarget::TYPE_CONTENT_ARCHIVE,
                    WidgetPlacementTarget::TYPE_CONTENT_SINGLE,
                ]),
            ],
            'placements.*.targets.*.target_ref' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('placements', []) as $index => $placement) {
                    if (
                        in_array($placement['scope'] ?? null, [PlacementScope::Only->value, PlacementScope::Except->value], true)
                        && empty($placement['targets'])
                    ) {
                        $validator->errors()->add(
                            "placements.{$index}.targets",
                            'Target wajib diisi untuk scope Only atau Except.',
                        );
                    }
                }
            },
        ];
    }
}
