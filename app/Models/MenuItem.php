<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LinkType;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $menu_id
 * @property ?int $parent_id
 * @property LinkType $link_type
 * @property ?string $link_ref
 * @property ?string $url
 * @property int $sort_order
 */
class MenuItem extends Model
{
    use HasTranslations;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'link_type',
        'link_ref',
        'url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => LinkType::class,
            'sort_order' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(MenuItemTranslation::class);
    }
}
