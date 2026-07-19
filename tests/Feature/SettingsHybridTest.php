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

it('setting_translated fallback ke default bila locale hilang', function () {
    SettingTranslation::create(['key' => 'site.tagline', 'language_id' => Language::idFor('id'), 'value' => 'ID Tagline']);

    app()->setLocale('en');
    expect(setting_translated('site.tagline'))->toBe('ID Tagline');
});

it('setting_translated null bila tidak ada', function () {
    expect(setting_translated('site.nonexistent'))->toBeNull();
});
