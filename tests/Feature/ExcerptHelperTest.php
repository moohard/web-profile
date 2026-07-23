<?php

it('menghapus semua tag HTML', function () {
    expect(excerpt('<p>Halo <strong>dunia</strong></p>'))->toBe('Halo dunia');
});

it('merapikan whitespace: spasi/newline berturut jadi satu spasi, trim ujung', function () {
    $html = "  Baris satu\n\n   Baris   dua\t\tBaris tiga  ";

    expect(excerpt($html))->toBe('Baris satu Baris dua Baris tiga');
});

it('memotong ke limit dan menambah elipsis bila teks melebihi limit', function () {
    $text = str_repeat('a', 200);

    $result = excerpt($text);

    expect($result)->toBe(str_repeat('a', 160).'…')
        ->and(mb_strlen($result))->toBe(161);
});

it('tepat di batas limit+1 tetap terpotong dan diberi elipsis (boundary off-by-one)', function () {
    $text = str_repeat('x', 161);

    $result = excerpt($text);

    expect($result)->toBe(str_repeat('x', 160).'…')
        ->and(mb_strlen($result))->toBe(161);
});

it('tidak menambah elipsis bila teks sama dengan atau kurang dari limit', function () {
    $tepatLimit = str_repeat('b', 160);

    expect(excerpt($tepatLimit))->toBe($tepatLimit)
        ->and(excerpt('Teks pendek.'))->toBe('Teks pendek.');
});

it('mengembalikan string kosong untuk input null', function () {
    expect(excerpt(null))->toBe('');
});

it('mengembalikan string kosong untuk input string kosong', function () {
    expect(excerpt(''))->toBe('');
});

it('mengembalikan string kosong bila HTML hanya berisi tag tanpa teks', function () {
    expect(excerpt('<p></p><div><span></span></div>'))->toBe('')
        ->and(excerpt('<p>   </p>'))->toBe('');
});

it('multibyte-safe: memotong di word boundary tanpa merusak karakter aksen/emoji', function () {
    $text = 'Café résumé 🎉 pizza';

    // Helper otoritatif (tests/Unit/ExcerptHelperTest) memotong di word boundary,
    // bukan di grapheme mentah — jadi 'Café résumé 🎉 pizza' dengan limit 8
    // menjadi 'Café…' (kata pertama utuh, sisanya dipotong di spasi).
    $hasil = excerpt($text, 8);
    expect($hasil)->toBe('Café…')
        ->and(mb_check_encoding($hasil, 'UTF-8'))->toBeTrue();

    // Limit long enough to keep the whole phrase incl. emoji.
    $hasilLengkap = excerpt($text, 100);
    expect($hasilLengkap)->toBe($text)
        ->and(mb_check_encoding($hasilLengkap, 'UTF-8'))->toBeTrue();
});

it('men-decode HTML entity setelah strip tag agar teks bersih untuk meta description', function () {
    $html = '<p>Untung &amp; rugi &ndash; tetap semangat</p>';

    expect(excerpt($html))->toBe('Untung & rugi – tetap semangat');
});

it('tidak membocorkan tag yang terbentuk dari entity setelah decode (cegah re-injection)', function () {
    $html = '<p>Contoh kode: &lt;script&gt;alert(1)&lt;/script&gt; adalah tag.</p>';

    $result = excerpt($html);

    expect($result)->not->toContain('<script')
        ->and($result)->not->toContain('<')
        ->and($result)->not->toContain('>')
        ->and($result)->toBe('Contoh kode: alert(1) adalah tag.');
});

it('tidak membocorkan tag img/onerror yang terbentuk dari entity setelah decode', function () {
    $html = 'Penjelasan payload: &lt;img src=x onerror=alert(1)&gt; adalah contoh XSS.';

    $result = excerpt($html);

    expect($result)->not->toContain('<img')
        ->and($result)->not->toContain('<')
        ->and($result)->not->toContain('>');
});

it('menerima parameter limit custom', function () {
    expect(excerpt('Satu dua tiga empat lima', 8))->toBe('Satu dua…');
});
