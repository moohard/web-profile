<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tag_id
 * @property int $language_id
 * @property string $name
 */
class TagTranslation extends Model
{
    protected $fillable = ['tag_id', 'language_id', 'name'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
