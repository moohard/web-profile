<?php

use App\Enums\AiTask;
use App\Enums\ContactStatus;
use App\Enums\TestimonialStatus;
use App\Models\AiConfig;
use App\Models\ContactMessage;
use App\Models\Language;
use App\Models\Rating;
use App\Models\RatingCriterion;
use App\Models\RatingScore;
use App\Models\Testimonial;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Language::flushCache();
    Language::query()->delete();
    Language::create(['code' => 'id', 'name' => 'Indonesia', 'is_default' => true]);
    Language::flushCache();
});

it('AiConfig api_key ter-enkripsi di DB tapi terbaca plain di model', function () {
    $cfg = AiConfig::create([
        'task' => AiTask::Translation,
        'base_url' => 'https://api.example.com/v1',
        'api_key' => 'secret-key-123',
        'model' => 'gpt-4o',
        'enabled' => true,
    ]);

    $raw = DB::table('ai_configs')->where('id', $cfg->id)->value('api_key');
    expect($raw)->not->toBe('secret-key-123');
    expect($cfg->fresh()->api_key)->toBe('secret-key-123');
});

it('AiConfig menyembunyikan api_key saat serialisasi ke array/json', function () {
    $cfg = AiConfig::create([
        'task' => AiTask::Translation,
        'base_url' => 'https://api.example.com/v1',
        'api_key' => 'secret-key-123',
        'enabled' => true,
    ]);

    // api_key tidak boleh ikut ter-serialisasi (mencegah bocor plaintext ke Inertia/JSON)
    expect($cfg->toArray())->not->toHaveKey('api_key')
        ->and($cfg->fresh()->toArray())->not->toHaveKey('api_key')
        ->and($cfg->toJson())->not->toContain('secret-key-123')
        // sanity: masih terbaca eksplisit lewat atribut (untuk pemakaian server-side)
        ->and($cfg->fresh()->api_key)->toBe('secret-key-123');
});

it('AiConfig::resolve mengembalikan konfigurasi enabled untuk task', function () {
    AiConfig::create([
        'task' => AiTask::Translation,
        'enabled' => true,
        'api_key' => 'k',
    ]);

    expect(AiConfig::resolve(AiTask::Translation))->not->toBeNull()
        ->and(AiConfig::resolve(AiTask::ContentRefinement))->toBeNull();
});

it('ContactMessage status cast', function () {
    $m = ContactMessage::create([
        'name' => 'A',
        'email' => 'a@b.c',
        'message' => 'halo',
    ]);

    expect($m->fresh()->status)->toBe(ContactStatus::New);
});

it('Testimonial status default Pending', function () {
    $t = Testimonial::create([
        'author_name' => 'A',
        'content' => 'bagus',
    ]);

    expect($t->fresh()->status)->toBe(TestimonialStatus::Pending);
});

it('Rating + RatingScore relasi', function () {
    $crit = RatingCriterion::create(['sort_order' => 1]);
    $r = Rating::create(['visitor_hash' => 'hash123']);
    RatingScore::create([
        'rating_id' => $r->id,
        'criterion_id' => $crit->id,
        'score' => 4,
    ]);

    expect($r->scores)->toHaveCount(1)
        ->and($r->scores->first()->score)->toBe(4);
});
