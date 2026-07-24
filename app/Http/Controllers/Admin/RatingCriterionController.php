<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RatingCriterionRequest;
use App\Models\Language;
use App\Models\RatingCriterion;
use App\Models\RatingCriterionTranslation;
use App\Support\PublicLayoutProps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RatingCriterionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', RatingCriterion::class);

        return Inertia::render('admin/rating-criteria/index', [
            'criteria' => RatingCriterion::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (RatingCriterion $criterion): array => $this->criterionData($criterion))
                ->values()->all(),
            'languages' => Language::active()
                ->get(['id', 'code', 'name'])
                ->map(fn (Language $language): array => [
                    'id' => $language->id,
                    'code' => $language->code,
                    'name' => $language->name,
                ])
                ->all(),
        ]);
    }

    public function store(RatingCriterionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $criterion = RatingCriterion::query()->create([
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ]);

            $this->syncTranslations($criterion, $data['translations']);
        });

        PublicLayoutProps::flushCache();

        return back()->with('success', 'Kriteria penilaian berhasil dibuat.');
    }

    public function update(RatingCriterionRequest $request, RatingCriterion $ratingCriterion): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $ratingCriterion): void {
            $ratingCriterion->update([
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ]);

            $this->syncTranslations($ratingCriterion, $data['translations']);
        });

        PublicLayoutProps::flushCache();

        return back()->with('success', 'Kriteria penilaian berhasil diperbarui.');
    }

    public function destroy(RatingCriterion $ratingCriterion): RedirectResponse
    {
        $this->authorize('delete', $ratingCriterion);

        $ratingCriterion->delete();
        PublicLayoutProps::flushCache();

        return back()->with('success', 'Kriteria penilaian berhasil dihapus.');
    }

    /**
     * @param  list<array{language_id: int, name: string}>  $translations
     */
    private function syncTranslations(RatingCriterion $criterion, array $translations): void
    {
        foreach ($translations as $translation) {
            $criterion->translations()->updateOrCreate(
                ['language_id' => $translation['language_id']],
                ['name' => $translation['name']],
            );
        }
    }

    /**
     * @return array{id: int, is_active: bool, sort_order: int, translations: array<int, array{language_id: int, name: string}>}
     */
    private function criterionData(RatingCriterion $criterion): array
    {
        return [
            'id' => $criterion->id,
            'is_active' => $criterion->is_active,
            'sort_order' => $criterion->sort_order,
            'translations' => $criterion->translations
                ->map(fn (RatingCriterionTranslation $translation): array => [
                    'language_id' => $translation->language_id,
                    'name' => $translation->name,
                ])
                ->all(),
        ];
    }
}
