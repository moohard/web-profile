<?php

declare(strict_types=1);

it('form Post memakai featured_media_id dan quick-create Tag typed', function () {
    $source = file_get_contents(resource_path('js/pages/admin/posts/form.tsx'));

    expect($source)
        ->toContain('featured_media_id')
        ->toContain('quickStore.url()')
        ->toContain('useHttp<')
        ->toContain('Tambah tag')
        ->not->toContain('featured_image')
        ->not->toContain("'/admin/tags");
});

it('archive publik merender responsive cards, pagination, empty, dan loading state', function () {
    $source = file_get_contents(resource_path('js/pages/public/post-archive.tsx'));

    expect($source)
        ->toContain('post.featured.srcset || undefined')
        ->toContain('posts.links.map')
        ->toContain('Belum ada konten')
        ->toContain('Memuat konten')
        ->toContain('aria-busy={loading}');
});

it('single Post merender featured, category, tags, tanggal, dan body statis', function () {
    $source = file_get_contents(resource_path('js/pages/public/post-show.tsx'));

    expect($source)
        ->toContain('srcSet={post.featured.srcset || undefined}')
        ->toContain('post.category')
        ->toContain('post.tags.map')
        ->toContain('post.published_at')
        ->toContain('dangerouslySetInnerHTML');
});

it('admin Post menampilkan status bahasa dan pagination dengan loading state', function () {
    $source = file_get_contents(resource_path('js/pages/admin/posts/index.tsx'));

    expect($source)
        ->toContain('row.statuses.map')
        ->toContain('posts.links.map')
        ->toContain('Memuat posts')
        ->toContain('preserveState: true')
        ->toContain('preserveScroll: true');
});

it('admin Category merender indent berdasarkan depth', function () {
    $source = file_get_contents(resource_path('js/pages/admin/categories/index.tsx'));

    expect($source)
        ->toContain('depth: number')
        ->toContain('paddingLeft')
        ->toContain('category.depth');
});
