<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\TagPolicy;
use App\Support\HasTranslations;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 */
#[UsePolicy(TagPolicy::class)]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['slug'];

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }

    /** @return HasMany<TagTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(TagTranslation::class);
    }
}
