<?php

use App\Http\Middleware\SetLocale;
use App\Models\Language;
use App\Support\LocaleUrl;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('LocaleUrl::for locale default tanpa prefix', function () {
    expect(LocaleUrl::for('id', '/berita/slug'))->toBe('/berita/slug')
        ->and(LocaleUrl::for('id', '/'))->toBe('/');
});

it('LocaleUrl::for locale non-default ber-prefix', function () {
    expect(LocaleUrl::for('en', '/berita/slug'))->toBe('/en/berita/slug')
        ->and(LocaleUrl::for('en', '/'))->toBe('/en');
});

it('LocaleUrl::isNonDefaultLocale', function () {
    expect(LocaleUrl::isNonDefaultLocale('en'))->toBeTrue()
        ->and(LocaleUrl::isNonDefaultLocale('id'))->toBeFalse()
        ->and(LocaleUrl::isNonDefaultLocale('fr'))->toBeFalse();
});

it('LocaleUrl::current mengikuti app locale', function () {
    app()->setLocale('en');
    expect(LocaleUrl::current())->toBe('en');

    app()->setLocale('id');
    expect(LocaleUrl::current())->toBe('id');
});

it('SetLocale middleware set locale dari segment URL non-default', function () {
    $middleware = new SetLocale;
    $request = Request::create('/en/berita/slug', 'GET');

    $middleware->handle($request, function ($req) {
        expect(app()->getLocale())->toBe('en')
            ->and($req->path())->toBe('berita/slug');

        return response('ok');
    });
});

it('SetLocale middleware fallback ke default bila segment bukan locale', function () {
    $middleware = new SetLocale;
    $request = Request::create('/berita/slug', 'GET');

    $middleware->handle($request, function ($req) {
        expect(app()->getLocale())->toBe('id')
            ->and($req->path())->toBe('berita/slug');

        return response('ok');
    });
});
