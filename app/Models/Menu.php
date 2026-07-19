<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MenuLocation;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property MenuLocation $location
 */
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected $fillable = ['name', 'location'];

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
        ];
    }

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<MenuItem, $this> */
    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAt(Builder $query, MenuLocation $location): Builder
    {
        return $query->where('location', $location->value);
    }
}
