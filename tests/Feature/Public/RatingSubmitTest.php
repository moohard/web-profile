<?php

declare(strict_types=1);

use App\Models\Rating;
use App\Models\RatingCriterion;
use App\Models\RatingScore;
use App\Support\PublicLayoutProps;
use Database\Factories\RatingFactory;
use Database\Factories\RatingScoreFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    $this->withoutVite();
    Cache::flush();
    PublicLayoutProps::flushCache();
    RateLimiter::clear(md5('rating-submit127.0.0.1'));
});

function ratingCriteria(): Collection
{
    return RatingCriterion::query()->active()->get();
}

function ratingPayload(int $score = 4): array
{
    return [
        'scores' => ratingCriteria()
            ->map(fn (RatingCriterion $criterion): array => [
                'criterion_id' => $criterion->id,
                'score' => $score,
            ])
            ->all(),
        'comment' => 'Layanan mudah digunakan.',
    ];
}

it('menyimpan satu rating situs beserta skor untuk setiap kriteria aktif', function () {
    $this->withHeader('User-Agent', 'rating-submit-test')
        ->post('/rating', ratingPayload())
        ->assertRedirect();

    $rating = Rating::query()->firstOrFail();

    expect($rating->visitor_hash)->toBe(hash('sha256', '127.0.0.1rating-submit-test'.config('app.key')))
        ->and($rating->comment)->toBe('Layanan mudah digunakan.')
        ->and($rating->scores)->toHaveCount(ratingCriteria()->count());

    expect(RatingScore::query()->where('rating_id', $rating->id)->pluck('score')->unique()->all())
        ->toBe([4]);
});

it('menolak submit ulang dari visitor hash yang sama tanpa menggandakan record', function () {
    $this->post('/rating', ratingPayload())->assertRedirect();

    $this->post('/rating', ratingPayload(5))
        ->assertSessionHasErrors('rating');

    expect(Rating::query()->count())->toBe(1)
        ->and(RatingScore::query()->count())->toBe(ratingCriteria()->count());
});

it('membatasi submit rating keempat dalam satu menit', function () {
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->withHeader('User-Agent', "rating-test-{$attempt}")
            ->post('/rating', ratingPayload())
            ->assertRedirect();
    }

    $this->withHeader('User-Agent', 'rating-test-4')
        ->post('/rating', ratingPayload())
        ->assertStatus(429);
});

it('membagikan agregasi rata-rata per kriteria pada prop layout publik', function () {
    $criteria = ratingCriteria();
    $firstRating = RatingFactory::new()->create();
    $secondRating = RatingFactory::new()->create();

    foreach ($criteria as $criterion) {
        RatingScoreFactory::new()->create([
            'rating_id' => $firstRating->id,
            'criterion_id' => $criterion->id,
            'score' => 4,
        ]);
        RatingScoreFactory::new()->create([
            'rating_id' => $secondRating->id,
            'criterion_id' => $criterion->id,
            'score' => 2,
        ]);
    }

    Cache::flush();
    PublicLayoutProps::flushCache();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rating.totalRespondents', 2)
            ->has('rating.criteria', $criteria->count())
            ->where('rating.criteria.0.average', 3)
        );
});
