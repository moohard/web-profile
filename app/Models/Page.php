<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PageMode;
use App\Policies\PagePolicy;
use App\Support\HasTranslations;
use App\Support\Media\HasDefaultMediaConversions;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property PageMode $mode
 * @property string $template_key
 * @property bool $hero_enabled
 * @property ?string $hero_image
 * @property bool $sidebar_enabled
 */
#[UsePolicy(PagePolicy::class)]
class Page extends Model implements HasMedia
{
    use HasDefaultMediaConversions, InteractsWithMedia {
        HasDefaultMediaConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'mode',
        'template_key',
        'hero_enabled',
        'hero_image',
        'sidebar_enabled',
    ];

    protected function casts(): array
    {
        return [
            'mode' => PageMode::class,
            'hero_enabled' => 'boolean',
            'sidebar_enabled' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_image')->singleFile();
    }

    /** @return HasMany<PageTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }
}
