<?php

use App\Models\Language;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();

    $this->idLang = Language::create([
        'code' => 'id',
        'name' => 'Bahasa Indonesia',
        'is_default' => true,
        'sort_order' => 1,
    ]);
    $this->enLang = Language::create([
        'code' => 'en',
        'name' => 'English',
        'sort_order' => 2,
    ]);

    Language::flushCache();
});

it('idFor mengembalikan id yang benar', function () {
    expect(Language::idFor('id'))->toBeInt()->toBe($this->idLang->id)
        ->and(Language::idFor('en'))->toBeInt()->toBe($this->enLang->id);
});

it('defaultModel mengembalikan bahasa is_default=true', function () {
    expect(Language::defaultModel()->code)->toBe('id');
});

it('current mengikuti app locale', function () {
    app()->setLocale('en');
    expect(Language::current()->code)->toBe('en');
    app()->setLocale('id');
    expect(Language::current()->code)->toBe('id');
});

it('idFor throw bila code tidak dikenal', function () {
    Language::idFor('fr');
})->throws(RuntimeException::class);
