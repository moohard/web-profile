<?php

return [
    // `?:` (bukan hanya env() default) agar nilai present-but-empty di .env
    // tetap jatuh ke default — env() hanya fallback saat key benar-benar absen.
    'email' => env('ADMIN_EMAIL') ?: 'admin@papenajam.test',
    'password' => env('ADMIN_PASSWORD') ?: 'password',
];
