<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property ?int $parent_id
 * @property int $sort_order
 */
class Category extends Model
{
    use HasTranslations;

    protected $fillable = ['slug', 'parent_id', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }
}
