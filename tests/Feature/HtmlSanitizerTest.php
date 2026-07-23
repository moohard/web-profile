<?php

use App\Services\Html\Sanitizer;

it('profile rich text mempertahankan struktur editor yang diizinkan', function () {
    $html = <<<'HTML'
    <h2>Judul</h2><p>Paragraf <strong>tebal</strong> dan <em>miring</em><br></p>
    <ul><li>Satu</li></ul><ol><li>Dua</li></ol>
    <blockquote>Kutipan</blockquote>
    <a href="https://example.com" title="Aman">Tautan</a>
    <img src="https://example.com/image.jpg" alt="Gambar" title="Judul">
    HTML;

    $clean = app(Sanitizer::class)->cleanRichText($html);

    expect($clean)
        ->toContain('<h2>Judul</h2>')
        ->toContain('<strong>tebal</strong>')
        ->toContain('<em>miring</em>')
        ->toContain('<ul><li>Satu</li></ul>')
        ->toContain('<ol><li>Dua</li></ol>')
        ->toContain('<blockquote>Kutipan</blockquote>')
        ->toContain('href="https://example.com"')
        ->toContain('src="https://example.com/image.jpg"');
});

it('profile rich text membuang script event style class dan URI berbahaya', function () {
    $html = <<<'HTML'
    <script>alert(1)</script>
    <p class="tracking" style="color:red" onclick="evil()">Aman</p>
    <a href="javascript:alert(1)">Bahaya</a>
    <img src="data:image/svg+xml;base64,PHN2Zy8+" onerror="evil()" alt="X">
    HTML;

    $clean = app(Sanitizer::class)->cleanRichText($html);

    expect($clean)
        ->toContain('<p>Aman</p>')
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('class=')
        ->not->toContain('style=')
        ->not->toContain('javascript:')
        ->not->toContain('data:');
});

it('profile cms page tetap mempertahankan class design system', function () {
    $clean = app(Sanitizer::class)->cleanCmsPage(
        '<section class="hero bg-blue-500"><h1 class="text-3xl">Hi</h1></section>',
    );

    expect($clean)->toContain('class="hero bg-blue-500"')->toContain('text-3xl');
});

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
