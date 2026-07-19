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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

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

    public static function defaultModel(): self
    {
        return Cache::rememberForever('language.default', fn () => static::default()->firstOrFail()
        );
    }

    public static function current(): self
    {
        return static::where('code', app()->getLocale())->first() ?? static::defaultModel();
    }

    /** Reset cache — panggil setelah seeder / perubahan tabel languages. */
    public static function flushCache(): void
    {
        Cache::forget('language.default');
        // keys dinamis per-code tidak bisa di-flush selektif; flush prefix:
        Cache::flush(); // aman di dev; di prod pakai Cache::tags bila didukung
    }
}
