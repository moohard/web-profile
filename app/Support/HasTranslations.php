<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Trait untuk model host yang punya tabel *_translations.
 * Host wajib mendeklarasikan metode translations(): HasMany.
 *
 * @mixin Model
 */
trait HasTranslations
{
    /**
     * Ambil translation untuk locale aktif (atau locale yang diberikan).
     * Fallback ke bahasa default bila tidak ada.
     */
    public function translate(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('language_id', Language::idFor($locale))
            ?? $this->translations->firstWhere('language_id', Language::defaultModel()->id);
    }

    /**
     * Eager-load translation untuk locale aktif (dan fallback default).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithTranslation(Builder $query): Builder
    {
        return $query->with(['translations' => function (Relation $q) {
            $q->where('language_id', Language::idFor(app()->getLocale()))
                ->orWhere('language_id', Language::defaultModel()->id);
        }]);
    }
}
