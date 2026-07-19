<?php

use App\Services\Html\Sanitizer;

it('Sanitizer membuang script tag', function () {
    $clean = app(Sanitizer::class)->clean('<p>ok</p><script>alert(1)</script>');

    expect($clean)->not->toContain('<script>')
        ->and($clean)->toContain('<p>ok</p>');
});

it('Sanitizer membuang atribut on*', function () {
    $clean = app(Sanitizer::class)->clean('<p onclick="evil()">hi</p>');

    expect($clean)->not->toContain('onclick');
});

it('Sanitizer membuang javascript: URL', function () {
    $clean = app(Sanitizer::class)->clean('<a href="javascript:alert(1)">x</a>');

    expect($clean)->not->toContain('javascript:');
});

it('Sanitizer mempertahankan class design system', function () {
    $clean = app(Sanitizer::class)->clean('<section class="hero bg-blue-500"><h1 class="text-3xl">Hi</h1></section>');

    expect($clean)->toContain('class="hero bg-blue-500"')->toContain('text-3xl');
});
