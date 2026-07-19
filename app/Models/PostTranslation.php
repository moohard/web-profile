<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\PostTranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $post_id
 * @property int $language_id
 * @property string $slug
 * @property string $title
 * @property ?string $body
 * @property PostStatus $status
 * @property ?Carbon $published_at
 * @property ?string $meta_title
 * @property ?string $meta_description
 */
class PostTranslation extends Model
{
    /** @use HasFactory<PostTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'post_id',
        'language_id',
        'slug',
        'title',
        'body',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return BelongsTo<Language, $this> */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published->value)
            ->where(function (Builder $qq) {
                $qq->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
