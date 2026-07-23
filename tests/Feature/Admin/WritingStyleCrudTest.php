<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\ContentType;
use App\Models\User;
use App\Models\WritingStyle;
use Database\Seeders\WritingStyleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    $this->admin = User::query()->where('email', config('admin.email'))->firstOrFail();
});

it('hanya Admin dapat mengelola Writing Styles', function () {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);

    $this->actingAs($this->admin)
        ->get('/admin/writing-styles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/writing-styles/index')
            ->has('writingStyles')
        );

    $this->actingAs($editor)->get('/admin/writing-styles')->assertForbidden();
    $this->actingAs($editor)->post('/admin/writing-styles', [
        'name' => 'Editorial',
        'prompt' => 'Gunakan gaya editorial.',
    ])->assertForbidden();
});

it('memvalidasi nama unik dan panjang prompt', function () {
    $existing = WritingStyle::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post('/admin/writing-styles', ['name' => '', 'prompt' => null])
        ->assertSessionHasErrors('name');

    $this->actingAs($this->admin)
        ->post('/admin/writing-styles', ['name' => $existing->name, 'prompt' => null])
        ->assertSessionHasErrors('name');

    $this->actingAs($this->admin)
        ->post('/admin/writing-styles', [
            'name' => 'Prompt terlalu panjang',
            'prompt' => str_repeat('a', 10001),
        ])
        ->assertSessionHasErrors('prompt');
});

it('membuat dan memperbarui Writing Style', function () {
    $this->actingAs($this->admin)
        ->post('/admin/writing-styles', [
            'name' => 'Editorial Ringkas',
            'prompt' => 'Gunakan kalimat aktif dan ringkas.',
        ])
        ->assertRedirect('/admin/writing-styles');

    $style = WritingStyle::query()->where('name', 'Editorial Ringkas')->firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/writing-styles/{$style->id}", [
            'name' => 'Editorial Publik',
            'prompt' => 'Gunakan bahasa publik yang mudah dipahami.',
        ])
        ->assertRedirect('/admin/writing-styles');

    expect($style->fresh())
        ->name->toBe('Editorial Publik')
        ->prompt->toBe('Gunakan bahasa publik yang mudah dipahami.');
});

it('menghapus style yang belum dipakai dan menolak style yang direferensikan', function () {
    $unused = WritingStyle::factory()->create();
    $used = WritingStyle::factory()->create();
    ContentType::factory()->create(['writing_style_id' => $used->id]);

    $this->actingAs($this->admin)
        ->delete("/admin/writing-styles/{$unused->id}")
        ->assertRedirect('/admin/writing-styles');

    $this->actingAs($this->admin)
        ->delete("/admin/writing-styles/{$used->id}")
        ->assertSessionHasErrors('writing_style');

    $this->assertModelMissing($unused);
    $this->assertModelExists($used);
});

it('index menandai Writing Style yang sedang dipakai', function () {
    $style = WritingStyle::factory()->create(['name' => 'Dipakai']);
    ContentType::factory()->create(['writing_style_id' => $style->id]);

    $this->actingAs($this->admin)
        ->get('/admin/writing-styles')
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'writingStyles',
                fn ($styles) => collect($styles)->contains(
                    fn (array $item): bool => $item['id'] === $style->id
                        && $item['is_in_use'] === true,
                ),
            )
        );
});

it('WritingStyleSeeder idempotent dan mempertahankan referensi Content Type', function () {
    $style = WritingStyle::query()->firstOrFail();
    $contentType = ContentType::query()->firstOrFail();
    $contentType->update(['writing_style_id' => $style->id]);

    $this->seed(WritingStyleSeeder::class);

    expect($style->fresh())->not->toBeNull()
        ->and($contentType->fresh()->writing_style_id)->toBe($style->id)
        ->and(WritingStyle::query()->where('name', 'Formal Indonesia')->count())->toBe(1);
});
