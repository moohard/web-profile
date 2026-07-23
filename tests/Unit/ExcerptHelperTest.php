<?php

declare(strict_types=1);

it('mengembalikan string kosong untuk input kosong', function (?string $html) {
    expect(excerpt($html))->toBe('');
})->with([null, '', '   ', '<p> </p>']);

it('menghapus tag mendekode entity dan merapikan whitespace', function () {
    expect(excerpt("<p>Halo&nbsp; dunia</p>\n<strong>yang   rapi</strong>"))
        ->toBe('Halo dunia yang rapi');
});

it('membatasi teks Unicode dan menambah elipsis hanya ketika terpotong', function () {
    expect(excerpt('<p>😀😀😀😀😀</p>', 3))->toBe('😀😀😀…')
        ->and(excerpt('<p>ééé</p>', 3))->toBe('ééé');
});

it('tidak memotong kata dan tetap aman untuk grapheme gabungan', function () {
    expect(excerpt('Kabupaten Penajam Utara', 12))->toBe('Kabupaten…')
        ->and(excerpt('👨‍👩‍👧‍👦👨‍👩‍👧‍👦', 1))->toBe('👨‍👩‍👧‍👦…');
});

it('menggunakan batas default 160 karakter', function () {
    $text = str_repeat('a', 161);

    expect(excerpt($text))->toBe(str_repeat('a', 160).'…');
});
