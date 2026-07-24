<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Models\Language;
use App\Models\SettingTranslation;
use App\Settings\SeoSettings;
use App\Settings\SiteSettings;
use App\Settings\WhatsappSettings;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $site = app(SiteSettings::class);
        $seo = app(SeoSettings::class);
        $whatsapp = app(WhatsappSettings::class);
        $languages = Language::active()->get(['id', 'code', 'name']);

        return Inertia::render('admin/settings/index', [
            'site' => [
                'site_name' => $site->site_name,
                'logo_path' => $site->logo_path,
                'favicon_path' => $site->favicon_path,
                'address' => $site->address,
                'phone' => $site->phone,
                'email' => $site->email,
                'social_links' => $site->social_links,
                'maps_embed' => $site->maps_embed,
                'contact_notification_email' => $site->contact_notification_email,
            ],
            'seo' => [
                'default_meta_title' => $seo->default_meta_title,
                'default_meta_description' => $seo->default_meta_description,
                'og_default_image_path' => $seo->og_default_image_path,
                'default_og_type' => $seo->default_og_type,
            ],
            'footerText' => $languages->map(fn (Language $language): array => [
                'language_id' => $language->id,
                'value' => SettingTranslation::query()
                    ->where('key', 'site.footer_text')
                    ->where('language_id', $language->id)
                    ->value('value') ?? '',
            ])->all(),
            'whatsapp' => [
                'number' => $whatsapp->number,
                'enabled' => $whatsapp->enabled,
                'default_message' => $whatsapp->default_message,
            ],
            'languages' => $languages,
        ]);
    }

    public function update(SiteSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $site = app(SiteSettings::class);
        $seo = app(SeoSettings::class);
        $whatsapp = app(WhatsappSettings::class);

        $site->site_name = $data['site_name'];
        $site->logo_path = $data['logo_path'] ?? null;
        $site->favicon_path = $data['favicon_path'] ?? null;
        $site->address = $data['address'] ?? null;
        $site->phone = $data['phone'] ?? '';
        $site->email = $data['email'] ?? null;
        $site->social_links = $data['social_links'] ?? [];
        $site->maps_embed = $data['maps_embed'] ?? null;
        $site->contact_notification_email = $data['contact_notification_email'] ?? null;
        $site->save();

        $seo->default_meta_title = $data['default_meta_title'] ?? null;
        $seo->default_meta_description = $data['default_meta_description'] ?? null;
        $seo->og_default_image_path = $data['og_default_image_path'] ?? null;
        $seo->default_og_type = $data['default_og_type'];
        $seo->save();

        $whatsapp->number = $data['whatsapp_number'] ?? '';
        $whatsapp->enabled = (bool) $data['whatsapp_enabled'];
        $whatsapp->default_message = $data['whatsapp_default_message'] ?? '';
        $whatsapp->save();

        foreach ($data['footer_text'] as $footerText) {
            SettingTranslation::updateOrCreate(
                ['key' => 'site.footer_text', 'language_id' => $footerText['language_id']],
                ['value' => $footerText['value'] ?: null],
            );
        }

        setting_translated_flush('site.footer_text');
        PublicLayoutProps::flushCache();

        return back()->with('success', 'Pengaturan situs disimpan.');
    }
}
