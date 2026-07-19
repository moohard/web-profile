<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gallery_image_id
 * @property int $language_id
 * @property ?string $caption
 */
class GalleryImageTranslation extends Model
{
    protected $fillable = [
        'gallery_image_id',
        'language_id',
        'caption',
    ];

    public function galleryImage(): BelongsTo
    {
        return $this->belongsTo(GalleryImage::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
