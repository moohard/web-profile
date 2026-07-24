<?php

declare(strict_types=1);

it('mode Template memakai RichTextEditor dan Code mempertahankan textarea', function () {
    $source = file_get_contents(resource_path('js/pages/admin/pages/form.tsx'));

    expect($source)
        ->toContain('import { RichTextEditor }')
        ->toContain('<RichTextEditor')
        ->toContain('<textarea')
        ->toContain("form.data.mode === 'Code'");
});

it('Page menyediakan saran AI translate dan refine yang diterima ke state form', function () {
    $source = file_get_contents(resource_path('js/pages/admin/pages/form.tsx'));

    expect($source)
        ->toContain('aiRoutes.translate.url()')
        ->toContain('aiRoutes.refine.url()')
        ->toContain('label="Terjemahkan dengan AI"')
        ->toContain('label="Koreksi dengan AI"')
        ->not->toContain('apply-translation');
});

it('Page menyediakan preview typed dan pending integration Menu', function () {
    $source = file_get_contents(resource_path('js/pages/admin/pages/form.tsx'));

    expect($source)
        ->toContain('<PagePreviewDialog')
        ->toContain('pagesRoutes.preview.url()')
        ->toContain('Tambahkan ke menu')
        ->toContain('disabled');
});

it('renderer publik membedakan tiga template registry tanpa dynamic include', function () {
    $source = file_get_contents(resource_path('js/pages/public/page-show.tsx'));

    expect($source)
        ->toContain("'default'")
        ->toContain("'full-width'")
        ->toContain("'landing'")
        ->toContain('data-template={templateKey}')
        ->not->toContain('eval(')
        ->not->toContain('import(`');
});
