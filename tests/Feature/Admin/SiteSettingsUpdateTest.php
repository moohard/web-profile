<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Language;
use App\Models\SettingTranslation;
use App\Models\User;
use App\Settings\SeoSettings;
use App\Settings\SiteSettings;
use App\Settings\WhatsappSettings;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed();
    Cache::flush();
});

function siteSettingsAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('admin dapat melihat dan memperbarui pengaturan situs', function () {
    $this->actingAs(siteSettingsAdmin())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/settings/index')
            ->has('site')
            ->has('seo')
            ->has('whatsapp')
            ->has('languages', 2)
        );

    $this->actingAs(siteSettingsAdmin())
        ->put('/admin/settings', [
            'site_name' => 'Papenajam Baru',
            'logo_path' => '/storage/logo.svg',
            'favicon_path' => '/storage/favicon.ico',
            'address' => 'Jl. Nusantara No. 1',
            'phone' => '+62 811 1234',
            'email' => 'halo@example.test',
            'social_links' => [
                'instagram' => 'https://instagram.com/papenajam',
            ],
            'maps_embed' => '<iframe src="https://maps.example.test"></iframe>',
            'contact_notification_email' => 'notifikasi@example.test',
            'default_meta_title' => 'Papenajam',
            'default_meta_description' => 'Portal resmi Papenajam.',
            'og_default_image_path' => '/storage/og.jpg',
            'default_og_type' => 'website',
            'footer_text' => [
                ['language_id' => Language::idFor('id'), 'value' => 'Footer Indonesia'],
                ['language_id' => Language::idFor('en'), 'value' => 'Footer English'],
            ],
            'whatsapp_number' => '628123456789',
            'whatsapp_enabled' => true,
            'whatsapp_default_message' => 'Halo dari Papenajam',
        ])
        ->assertRedirect();

    Cache::flush();
    app()->forgetInstance(SiteSettings::class);
    app()->forgetInstance(SeoSettings::class);
    app()->forgetInstance(WhatsappSettings::class);

    expect(app(SiteSettings::class)->site_name)->toBe('Papenajam Baru')
        ->and(app(SiteSettings::class)->address)->toBe('Jl. Nusantara No. 1')
        ->and(app(SiteSettings::class)->social_links)->toBe([
            'instagram' => 'https://instagram.com/papenajam',
        ])
        ->and(app(SeoSettings::class)->default_meta_title)->toBe('Papenajam')
        ->and(app(WhatsappSettings::class)->number)->toBe('628123456789')
        ->and(app(WhatsappSettings::class)->enabled)->toBeTrue()
        ->and(SettingTranslation::query()
            ->where('key', 'site.footer_text')
            ->where('language_id', Language::idFor('id'))
            ->value('value'))->toBe('Footer Indonesia');
});

it('non-admin tanpa admin.access-system tidak dapat mengakses pengaturan situs', function () {
    $editor = User::factory()->create()->givePermissionTo('access-admin');

    $this->actingAs($editor)->get('/admin/settings')->assertForbidden();
    $this->actingAs($editor)->put('/admin/settings', [])->assertForbidden();
});

it('Author tidak dapat mengakses pengaturan situs', function () {
    $author = User::factory()->create()->assignRole(UserRole::Author->value);

    $this->actingAs($author)->get('/admin/settings')->assertForbidden();
    $this->actingAs($author)->put('/admin/settings', [])->assertForbidden();
});
