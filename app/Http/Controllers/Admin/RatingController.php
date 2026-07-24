<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Rating;
use App\Models\RatingCriterion;
use App\Models\RatingCriterionTranslation;
use App\Models\RatingScore;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    public function index(): Response
    {
        $aggregates = DB::table('rating_scores')
            ->selectRaw('criterion_id, AVG(score) as average, COUNT(DISTINCT rating_id) as total')
            ->groupBy('criterion_id')
            ->get()
            ->mapWithKeys(fn (\stdClass $aggregate): array => [
                (int) $aggregate->criterion_id => [
                    'average' => (float) $aggregate->average,
                    'total' => (int) $aggregate->total,
                ],
            ])
            ->all();
        $languageId = Language::current()->id;

        return Inertia::render('admin/ratings/index', [
            'totalRespondents' => Rating::query()->count(),
            'criteria' => RatingCriterion::query()
                ->with(['translations' => fn ($query) => $query->where('language_id', $languageId)])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (RatingCriterion $criterion): array => $this->criterionData($criterion, $aggregates))
                ->all(),
            'ratings' => Rating::query()
                ->with(['scores.criterion.translations' => fn ($query) => $query->where('language_id', $languageId)])
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (Rating $rating): array => [
                    'id' => $rating->id,
                    'comment' => $rating->comment,
                    'created_at' => $rating->created_at?->toIso8601String(),
                    'scores' => $rating->scores->map(fn (RatingScore $score): array => [
                        'criterion' => $score->criterion?->translations->first()?->name,
                        'score' => $score->score,
                    ])->all(),
                ])
                ->all(),
        ]);
    }

    /**
     * @param  array<int, array{average: float, total: int}>  $aggregates
     * @return array{id: int, name: string, average: float, total: int}
     */
    private function criterionData(RatingCriterion $criterion, array $aggregates): array
    {
        $aggregate = $aggregates[$criterion->id] ?? null;
        $translation = $criterion->translations->first();

        return [
            'id' => $criterion->id,
            'name' => $translation instanceof RatingCriterionTranslation ? $translation->name : 'Kriteria',
            'average' => $aggregate['average'] ?? 0.0,
            'total' => $aggregate['total'] ?? 0,
        ];
    }
}
