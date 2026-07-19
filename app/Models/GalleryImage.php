<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $gallery_id
 * @property string $path
 * @property int $sort_order
 */
class GalleryImage extends Model
{
    use HasTranslations;

    protected $fillable = ['gallery_id', 'path', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Gallery, $this> */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    /** @return HasMany<GalleryImageTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(GalleryImageTranslation::class);
    }
}
