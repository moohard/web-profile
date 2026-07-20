<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Helper slug unik global per model (mis. Category, Tag).
 */
class ContentSlug
{
    /**
     * Hasilkan slug unik untuk $modelClass dari $source (nama atau slug mentah).
     * Menambah suffix numerik (-2, -3, …) bila terjadi tabrakan.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'item';

        $slug = $base;
        $suffix = 2;

        while (
            $modelClass::query()
                ->where($column, $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
