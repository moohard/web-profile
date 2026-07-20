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

it('Sanitizer membuang elemen di luar allowlist', function () {
    $clean = app(Sanitizer::class)->clean('<p>ok</p><video src="x.mp4"></video><textarea>x</textarea>');
    expect($clean)->not->toContain('<video')->not->toContain('<textarea');
});

it('Sanitizer membuang data: URL', function () {
    $clean = app(Sanitizer::class)->clean('<a href="data:text/html,xss">x</a><img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="x">');

    expect($clean)->not->toContain('data:');
});

// --- cleanRichText() — profil `default`, dipakai editor Tiptap (body Post & konten mode Template Page) ---

it('cleanRichText mempertahankan tag format rich-text yang diizinkan', function () {
    $html = '<h1>Judul</h1><h2>Sub</h2><h3>Sub sub</h3>'
        .'<p>Paragraf <strong>tebal</strong> dan <em>miring</em>.</p>'
        .'<ul><li>Satu</li></ul><ol><li>Dua</li></ol>'
        .'<blockquote>Kutipan</blockquote>'
        .'<a href="https://example.com">tautan</a>'
        .'<img src="https://example.com/x.png" alt="x">'
        .'<br>';

    $clean = app(Sanitizer::class)->cleanRichText($html);

    expect($clean)->toContain('<h1>Judul</h1>')
        ->and($clean)->toContain('<h2>Sub</h2>')
        ->and($clean)->toContain('<h3>Sub sub</h3>')
        ->and($clean)->toContain('<strong>tebal</strong>')
        ->and($clean)->toContain('<em>miring</em>')
        ->and($clean)->toContain('<li>Satu</li>')
        ->and($clean)->toContain('<li>Dua</li>')
        ->and($clean)->toContain('<blockquote>Kutipan</blockquote>')
        ->and($clean)->toContain('href="https://example.com"')
        ->and($clean)->toContain('src="https://example.com/x.png"')
        ->and($clean)->toContain('<br');
});

it('cleanRichText membuang script tag', function () {
    $clean = app(Sanitizer::class)->cleanRichText('<p>ok</p><script>alert(1)</script>');

    expect($clean)->not->toContain('<script>')
        ->and($clean)->toContain('<p>ok</p>');
});

it('cleanRichText membuang atribut on*', function () {
    $clean = app(Sanitizer::class)->cleanRichText('<p onclick="evil()">hi</p>');

    expect($clean)->not->toContain('onclick');
});

it('cleanRichText membuang javascript: URL', function () {
    $clean = app(Sanitizer::class)->cleanRichText('<a href="javascript:alert(1)">x</a>');

    expect($clean)->not->toContain('javascript:');
});

it('cleanRichText berbeda dari clean — tag berbasis class (mis. div) di luar profil rich-text dibuang', function () {
    $html = '<div class="callout">Penting</div><p>Isi</p>';

    $richText = app(Sanitizer::class)->cleanRichText($html);
    $cmsPage = app(Sanitizer::class)->clean($html);

    expect($richText)->not->toContain('<div')
        ->and($richText)->toContain('Penting')
        ->and($cmsPage)->toContain('<div class="callout">');
});
