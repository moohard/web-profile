<?php

declare(strict_types=1);

namespace App\Actions\Ratings;

use App\Models\Rating;
use App\Models\RatingCriterion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreRating
{
    /**
     * @param  list<array{criterion_id: int, score: int}>  $scores
     */
    public function __invoke(string $visitorHash, array $scores, ?string $comment): Rating
    {
        return DB::transaction(function () use ($visitorHash, $scores, $comment): Rating {
            if (Rating::query()->where('visitor_hash', $visitorHash)->exists()) {
                throw ValidationException::withMessages([
                    'rating' => 'Anda sudah mengirim penilaian.',
                ]);
            }

            $activeCriterionIds = RatingCriterion::query()
                ->active()
                ->pluck('id')
                ->all();
            $submittedScores = collect($scores)->keyBy('criterion_id');

            if ($submittedScores->keys()->sort()->values()->all() !== $activeCriterionIds) {
                throw ValidationException::withMessages([
                    'scores' => 'Nilai harus diisi untuk setiap kriteria aktif.',
                ]);
            }

            $rating = Rating::query()->create([
                'visitor_hash' => $visitorHash,
                'comment' => $comment,
            ]);

            $rating->scores()->createMany(
                $submittedScores
                    ->map(fn (array $score): array => [
                        'criterion_id' => $score['criterion_id'],
                        'score' => $score['score'],
                    ])
                    ->values()
                    ->all(),
            );

            return $rating;
        });
    }
}
