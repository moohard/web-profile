<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 */
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_default', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /** Resolve code (mis. 'en') ke ID, dengan cache 1 jam. */
    public static function idFor(string $code): int
    {
        return Cache::remember("language.id_for.{$code}", now()->addHour(), fn () => static::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Language [{$code}] tidak ditemukan.")
        );
    }

    /**
     * Ambil model bahasa default.
     * Cache hanya menyimpan code (string) agar tidak terjadi __PHP_Incomplete_Class.
     */
    public static function defaultModel(): self
    {
        $code = Cache::remember('language.default_code', now()->addHour(), function () {
            return static::default()->value('code')
                ?? throw new \RuntimeException('Default language not found.');
        });

        return static::where('code', $code)->firstOrFail();
    }

    public static function current(): self
    {
        return static::where('code', app()->getLocale())->first() ?? static::defaultModel();
    }

    /**
     * Reset cache bahasa secara selektif — panggil setelah seeder / perubahan tabel languages.
     * Hanya menghapus key milik model ini (tidak Cache::flush global) agar cache lain aman.
     */
    public static function flushCache(): void
    {
        Cache::forget('language.default_code');
        Cache::forget('language.default');

        // Hapus key id_for.{code} untuk seluruh kode yang saat ini ada di tabel.
        foreach (static::query()->pluck('code') as $code) {
            Cache::forget("language.id_for.{$code}");
        }
    }
}
