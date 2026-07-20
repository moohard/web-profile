<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AiConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi utama via middleware permission:admin.access-system pada route.
        return $this->user()?->can('admin.access-system') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'base_url' => ['nullable', 'string', 'max:255', 'url'],
            'model' => ['nullable', 'string', 'max:255'],
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'enabled' => ['required', 'boolean'],
            // Kosong = pertahankan key tersimpan (pola secret non-destruktif).
            'api_key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
