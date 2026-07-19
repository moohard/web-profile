<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $post_id
 * @property int $tag_id
 */
class PostTag extends Model
{
    protected $table = 'post_tags';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['post_id', 'tag_id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
