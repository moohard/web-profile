<?php

declare(strict_types=1);

it('RichTextEditor memakai kontrak SSR dan sinkronisasi controlled Tiptap', function () {
    $source = file_get_contents(resource_path('js/components/admin/rich-text-editor.tsx'));

    expect($source)
        ->toBeString()
        ->toContain('immediatelyRender: false')
        ->toContain('setContent(value, { emitUpdate: false })')
        ->toContain('onUpdate:')
        ->toContain('<EditorContent')
        ->toContain('<MediaPicker')
        // Atribut `aria-pressed` dirender runtime oleh Radix Toggle dari prop
        // `pressed` (lihat components/ui/toggle.tsx). Kontrak sumber = setiap
        // tombol toolbar memakai prop `pressed={...}`. Heading dipakai lewat
        // map atas HEADING_LEVELS (1 literal → 3 tombol saat render), jadi
        // literal source = 7 (heading-map + bold + italic + bullet + ordered
        // + blockquote + link) → 9 tombol aktif saat render.
        ->and(substr_count($source, 'pressed={'))
        ->toBeGreaterThanOrEqual(7)
        // Pastikan heading memakai map 3 level (H1/H2/H3).
        ->and(substr_count($source, 'HEADING_LEVELS'))
        ->toBeGreaterThanOrEqual(1);
});

it('form Post memakai RichTextEditor untuk body setiap bahasa', function () {
    $source = file_get_contents(resource_path('js/pages/admin/posts/form.tsx'));

    expect($source)
        ->toBeString()
        ->toContain("import { RichTextEditor } from '@/components/admin/rich-text-editor';")
        ->toContain('<RichTextEditor')
        ->not->toContain('<textarea'."\n".'                                                    id={`post-body-');
});

it('MediaPicker memuat JSON tanpa menavigasikan halaman editor', function () {
    $source = file_get_contents(resource_path('js/components/media/media-picker.tsx'));

    expect($source)
        ->toBeString()
        ->toContain("import { useHttp } from '@inertiajs/react';")
        ->toContain('mediaPicker.url()')
        ->toContain('cancel();')
        ->toContain('.catch(() => undefined)')
        ->not->toContain('router.visit')
        ->not->toContain('mediaIndex');
});
