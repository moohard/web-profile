<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Ssr\HttpGateway;

beforeEach(function () {
    $this->seed();
});

it('form Post dapat dirender SSR saat Tiptap belum diinisialisasi di client', function () {
    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan.');
    }

    $admin = User::query()->where('email', config('admin.email'))->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin/posts/create')
        ->assertOk()
        ->assertSee('Tambah post')
        ->assertSee('Memuat Konten dalam Bahasa Indonesia');
});
