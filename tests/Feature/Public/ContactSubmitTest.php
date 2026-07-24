<?php

declare(strict_types=1);

use App\Enums\ContactStatus;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->withoutVite();
    RateLimiter::clear(md5('contact-submit127.0.0.1'));

    $settings = app(SiteSettings::class);
    $settings->contact_notification_email = 'inbox@example.test';
    $settings->save();
});

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.test',
        'message' => 'Saya ingin menanyakan informasi layanan.',
        'website' => '',
    ], $overrides);
}

it('menyimpan pesan kontak dan mengantrekan notifikasi email', function (): void {
    Mail::fake();

    $this->post('/kontak', contactPayload())->assertRedirect();

    $this->assertDatabaseHas('contact_messages', [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.test',
        'message' => 'Saya ingin menanyakan informasi layanan.',
        'status' => ContactStatus::New->value,
    ]);

    Mail::assertQueued(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
        return $mail->hasTo('inbox@example.test');
    });
});

it('menolak pesan kontak saat honeypot terisi tanpa menyimpannya', function (): void {
    Mail::fake();

    $this->from('/kontak')
        ->post('/kontak', contactPayload(['website' => 'https://spam.example.test']))
        ->assertRedirect('/kontak')
        ->assertSessionHasErrors('website');

    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('membatasi submit kontak menjadi lima request per menit per IP', function (): void {
    Mail::fake();

    foreach (range(1, 5) as $request) {
        $this->post('/kontak', contactPayload(['email' => "budi{$request}@example.test"]))
            ->assertRedirect();
    }

    $this->post('/kontak', contactPayload(['email' => 'dibatasi@example.test']))
        ->assertTooManyRequests();
});
