<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Bersihkan state rate limiter sebelum tiap test (menggunakan key yang di-hash oleh ThrottleRequests middleware default)
    // Key format: md5(limiterName . byValue) karena $shouldHashKeys = true
    RateLimiter::clear(md5('contact-submit127.0.0.1'));
    RateLimiter::clear(md5('testimonial-submit127.0.0.1'));
    RateLimiter::clear(md5('rating-submit127.0.0.1'));
});

test('contact-submit limiter mengizinkan 5 request lalu menolak request ke-6 dengan 429', function () {
    $url = '/__throttle-test-contact';

    Route::post($url, fn () => response()->noContent())
        ->middleware('throttle:contact-submit');

    // 5 request pertama harus sukses (204)
    for ($i = 0; $i < 5; $i++) {
        $this->post($url)->assertNoContent();
    }

    // Request ke-6 harus ditolak
    $this->post($url)->assertTooManyRequests();
});

test('testimonial-submit limiter mengizinkan 3 request lalu menolak request ke-4 dengan 429', function () {
    $url = '/__throttle-test-testimonial';

    Route::post($url, fn () => response()->noContent())
        ->middleware('throttle:testimonial-submit');

    for ($i = 0; $i < 3; $i++) {
        $this->post($url)->assertNoContent();
    }

    $this->post($url)->assertTooManyRequests();
});

test('rating-submit limiter mengizinkan 3 request lalu menolak request ke-4 dengan 429', function () {
    $url = '/__throttle-test-rating';

    Route::post($url, fn () => response()->noContent())
        ->middleware('throttle:rating-submit');

    for ($i = 0; $i < 3; $i++) {
        $this->post($url)->assertNoContent();
    }

    $this->post($url)->assertTooManyRequests();
});
