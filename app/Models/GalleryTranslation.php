<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gallery_id
 * @property int $language_id
 * @property string $title
 * @property ?string $description
 */
class GalleryTranslation extends Model
{
    protected $fillable = [
        'gallery_id',
        'language_id',
        'title',
        'description',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
