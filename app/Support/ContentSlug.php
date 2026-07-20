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
     * @param  array<string, mixed>  $wheres  Kondisi tambahan untuk menyempitkan cek keunikan
     *                                        (mis. ['language_id' => $id] agar slug unik per bahasa).
     */
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null, string $column = 'slug', array $wheres = []): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'item';

        $slug = $base;
        $suffix = 2;

        while (
            $modelClass::query()
                ->where($column, $slug)
                ->where($wheres)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
