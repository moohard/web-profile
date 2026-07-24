<?php

use App\Models\Language;
use App\Models\SettingTranslation;
use App\Settings\SeoSettings;
use App\Settings\SiteSettings;
use App\Settings\WhatsappSettings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::create(['code' => 'en', 'name' => 'English']);
    Language::flushCache();
    Cache::flush();
});

it('Spatie SiteSettings menyimpan dan membaca nilai', function () {
    $s = app(SiteSettings::class);
    $s->site_name = 'Papenajam';
    $s->save();

    expect(app(SiteSettings::class)->site_name)->toBe('Papenajam');
});

it('Spatie SiteSettings menyimpan dan membaca kontak, sosial, dan Maps', function () {
    $settings = app(SiteSettings::class);
    $settings->address = 'Jl. Nusantara No. 1';
    $settings->phone = '021-xxx';
    $settings->email = 'halo@example.test';
    $settings->social_links = ['instagram' => 'https://instagram.com/example'];
    $settings->maps_embed = '<iframe src="https://maps.example.test"></iframe>';
    $settings->contact_notification_email = 'notifikasi@example.test';
    $settings->save();

    app()->forgetInstance(SiteSettings::class);
    $savedSettings = app(SiteSettings::class);

    expect($savedSettings->address)->toBe('Jl. Nusantara No. 1')
        ->and($savedSettings->phone)->toBe('021-xxx')
        ->and($savedSettings->email)->toBe('halo@example.test')
        ->and($savedSettings->social_links)->toBe(['instagram' => 'https://instagram.com/example'])
        ->and($savedSettings->maps_embed)->toBe('<iframe src="https://maps.example.test"></iframe>')
        ->and($savedSettings->contact_notification_email)->toBe('notifikasi@example.test');
});

it('Spatie WhatsappSettings dan SeoSettings resolve dengan default', function () {
    expect(app(WhatsappSettings::class)->enabled)->toBeFalse()
        ->and(app(WhatsappSettings::class)->number)->toBe('')
        ->and(app(SeoSettings::class)->default_og_type)->toBe('website');
});

it('setting_translated mengembalikan nilai locale aktif', function () {
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('id'), 'value' => 'ID Tagline']);
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('en'), 'value' => 'EN Tagline']);

    app()->setLocale('en');
    expect(setting_translated('site.tagline'))->toBe('EN Tagline');

    setting_translated_flush('site.tagline');

    app()->setLocale('id');
    expect(setting_translated('site.tagline'))->toBe('ID Tagline');
});

it('setting_translated menyimpan footer text per locale', function () {
    SettingTranslation::create(['key' => 'site.footer_text', 'language_id' => Language::idFor('id'), 'value' => 'Footer Indonesia']);
    SettingTranslation::create(['key' => 'site.footer_text', 'language_id' => Language::idFor('en'), 'value' => 'English footer']);

    expect(setting_translated('site.footer_text', 'id'))->toBe('Footer Indonesia')
        ->and(setting_translated('site.footer_text', 'en'))->toBe('English footer');
});

it('setting_translated fallback ke default bila locale hilang', function () {
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('id'), 'value' => 'ID Tagline']);

    app()->setLocale('en');
    expect(setting_translated('site.tagline'))->toBe('ID Tagline');
});

it('setting_translated null bila tidak ada', function () {
    expect(setting_translated('site.nonexistent'))->toBeNull();
});
