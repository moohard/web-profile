<?php

use App\Models\Language;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
