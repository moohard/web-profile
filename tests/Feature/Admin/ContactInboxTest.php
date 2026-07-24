<?php

declare(strict_types=1);

use App\Enums\ContactStatus;
use App\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withoutVite();
});

function contactInboxAdmin(): User
{
    return User::query()->where('email', config('admin.email'))->firstOrFail();
}

it('menampilkan inbox pesan kontak', function (): void {
    ContactMessage::factory()->create([
        'name' => 'Siti Aminah',
        'status' => ContactStatus::New,
    ]);

    $this->actingAs(contactInboxAdmin())
        ->get('/admin/contact-messages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contact-messages/index')
            ->has('messages', 1)
            ->where('messages.0.name', 'Siti Aminah')
            ->where('messages.0.status', ContactStatus::New->value)
        );
});

it('mengubah status dan menghapus pesan kontak', function (): void {
    $message = ContactMessage::factory()->create(['status' => ContactStatus::New]);

    $this->actingAs(contactInboxAdmin())
        ->put("/admin/contact-messages/{$message->id}", ['status' => ContactStatus::Read->value])
        ->assertRedirect();

    expect($message->fresh()->status)->toBe(ContactStatus::Read);

    $this->actingAs(contactInboxAdmin())
        ->delete("/admin/contact-messages/{$message->id}")
        ->assertRedirect();

    expect(ContactMessage::find($message->id))->toBeNull();
});

it('mengizinkan Admin dan Editor serta menolak Author sesuai ContactMessagePolicy', function (): void {
    $editor = User::factory()->create()->assignRole(UserRole::Editor->value);
    $author = User::factory()->create()->assignRole(UserRole::Author->value);
    $message = ContactMessage::factory()->create();

    $this->actingAs($editor)->get('/admin/contact-messages')->assertOk();
    $this->actingAs($editor)
        ->put("/admin/contact-messages/{$message->id}", ['status' => ContactStatus::Archived->value])
        ->assertRedirect();
    $this->actingAs($editor)->delete("/admin/contact-messages/{$message->id}")->assertRedirect();

    $message = ContactMessage::factory()->create();

    $this->actingAs($author)->get('/admin/contact-messages')->assertForbidden();
    $this->actingAs($author)
        ->put("/admin/contact-messages/{$message->id}", ['status' => ContactStatus::Read->value])
        ->assertForbidden();
    $this->actingAs($author)->delete("/admin/contact-messages/{$message->id}")->assertForbidden();
});
