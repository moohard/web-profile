<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LanguageRequest extends FormRequest
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
        $language = $this->route('language');

        return [
            'code' => [
                'required',
                'string',
                'regex:/^[a-z]{2}$/',
                Rule::notIn(['up']),
                Rule::unique(Language::class, 'code')->ignore($language),
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $language = $this->route('language');
                $isActive = $this->boolean('is_active');
                $isDefault = $this->boolean('is_default');

                if ($isDefault && ! $isActive) {
                    $validator->errors()->add('is_active', 'Bahasa default wajib aktif.');
                }

                if ($language instanceof Language && $language->is_default && ! $isDefault) {
                    $validator->errors()->add('is_default', 'Pilih bahasa lain sebagai default terlebih dahulu.');
                }

                if (
                    $language instanceof Language
                    && $language->code !== $this->string('code')->toString()
                    && $language->isInUse()
                ) {
                    $validator->errors()->add('code', 'Kode bahasa tidak dapat diubah setelah bahasa dipakai.');
                }
            },
        ];
    }

    /**
     * @return array{code: string, name: string, is_active: bool, is_default: bool, sort_order: int}
     */
    public function languageData(): array
    {
        return [
            'code' => $this->string('code')->toString(),
            'name' => $this->string('name')->toString(),
            'is_active' => $this->boolean('is_active'),
            'is_default' => $this->boolean('is_default'),
            'sort_order' => $this->integer('sort_order'),
        ];
    }
}
