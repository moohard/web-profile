<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access-system') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:2048'],
            'favicon_path' => ['nullable', 'string', 'max:2048'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
            'maps_embed' => ['nullable', 'string', 'max:10000'],
            'contact_notification_email' => ['nullable', 'email', 'max:255'],
            'default_meta_title' => ['nullable', 'string', 'max:255'],
            'default_meta_description' => ['nullable', 'string', 'max:1000'],
            'og_default_image_path' => ['nullable', 'string', 'max:2048'],
            'default_og_type' => ['required', 'in:website,article,profile'],
            'footer_text' => ['required', 'array'],
            'footer_text.*.language_id' => ['required', 'integer', 'exists:languages,id', 'distinct'],
            'footer_text.*.value' => ['nullable', 'string', 'max:5000'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'whatsapp_default_message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
