<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $type_id
 * @property ?int $category_id
 * @property ?string $featured_image
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['type_id', 'category_id', 'featured_image'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ContentType::class, 'type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }
}
