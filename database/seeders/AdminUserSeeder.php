<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Skip di production; izinkan local + testing agar SeedersTest lolos
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@papenajam.test')],
            [
                'name' => 'Administrator',
                // Password cast "hashed" — kirim plain text agar tidak double-hash
                'password' => env('ADMIN_PASSWORD', 'password'),
            ]
        );
        // email_verified_at tidak di $fillable
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(UserRole::Admin->value);
    }
}
