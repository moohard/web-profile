<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Language;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Support\ContentSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FindOrCreateTag
{
    /**
     * @return array{tag: Tag, created: bool}
     */
    public function __invoke(int $languageId, string $name): array
    {
        return DB::transaction(function () use ($languageId, $name): array {
            $lockedLanguageIds = Language::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            abort_unless($lockedLanguageIds->contains($languageId), 404);

            $normalizedName = Str::lower($name);
            $existingTranslation = TagTranslation::query()
                ->where('language_id', $languageId)
                ->whereRaw('lower(name) = ?', [$normalizedName])
                ->lockForUpdate()
                ->first();

            if ($existingTranslation !== null) {
                return [
                    'tag' => $existingTranslation->tag()->firstOrFail(),
                    'created' => false,
                ];
            }

            $baseSlug = Str::slug($name);
            $tag = Tag::query()
                ->where('slug', $baseSlug)
                ->whereDoesntHave(
                    'translations',
                    fn ($query) => $query->where('language_id', $languageId),
                )
                ->lockForUpdate()
                ->first();

            if ($tag === null) {
                $tag = Tag::create([
                    'slug' => ContentSlug::unique(Tag::class, $name),
                ]);
            }

            $tag->translations()->create([
                'language_id' => $languageId,
                'name' => $name,
            ]);

            return ['tag' => $tag, 'created' => true];
        }, attempts: 3);
    }
}
