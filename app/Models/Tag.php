<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 */
class Tag extends Model
{
    use HasTranslations;

    protected $fillable = ['slug'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TagTranslation::class);
    }
}
